#include "esp_camera.h"
#include <WiFi.h>
#include <WiFiUdp.h>
#include <ArduinoWebsockets.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#define CAMERA_MODEL_AI_THINKER
#include "camera_pins.h"
#include "esp_timer.h"
#include "img_converters.h"
#include "Arduino.h"
#include "fb_gfx.h"
#include "soc/soc.h" //disable brownout problems
#include "soc/rtc_cntl_reg.h"  //disable brownout problems
#include "esp_http_server.h"
#include <ESP32Servo.h>
#include "esp_system.h"
#include <ArduinoOTA.h>
#include "secrets.h"
#include "esp_heap_caps.h"

#define PART_BOUNDARY "123456789000000000000987654321"

#define SERVO_1      14
#define SERVO_2      15

#define SERVO_STEP   5

Servo servo1;
Servo servo2;

int servo1Pos = 90;
int servo2Pos = 90;

String temp;
const char* cameraNum = TO_STRING(HOST_IDENT);
const char* OTA_HOSTNAME = (String("ESP32-CAM-") + String(cameraNum)).c_str();

const char* ssid = WIFI_SSID;
const char* password = OTA_PASSWORD;
const char* websocket_server_host = WEBSOCK_HOST;
const uint16_t websocket_server_port = WEBSOCK_PORT;
const int udp_port = UDP_PORT;

bool otaInProgress = false;

#ifdef __cplusplus
extern "C" {
#endif
uint8_t temprature_sens_read();
#ifdef __cplusplus
}
#endif
uint8_t temprature_sens_read();

const int oneWireBus = 2;
OneWire oneWire(oneWireBus);
DallasTemperature sensors(&oneWire);

using namespace websockets;
WebsocketsClient client;
bool isWebSocketConnected;

WiFiUDP udp;

const int timerInterval = 30000;    // time between each HTTP POST image
unsigned long previousMillis = 0;   // last time image was sent

void setup() {
  isWebSocketConnected = false;
  Serial.begin(115200);
  Serial.setDebugOutput(true);
  Serial.println();
  xTaskCreatePinnedToCore(
    monitorTask,    // Function with the monitoring logic
    "MonitorTask",  // Name for the task
    2048,           // Stack size in bytes for monitorTask
    NULL,           // No parameters needed right now
    1,              // Lower priority than captureTask
    NULL,           // Task handle
    1               // Run on core 1
  );


  sensors.begin();

  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0); //disable brownout detector
  servo1.setPeriodHertz(50);    // standard 50 hz servo
  servo2.setPeriodHertz(50);    // standard 50 hz servo
  
  servo1.attach(SERVO_1, 1000, 2000);
  servo2.attach(SERVO_2, 1000, 2000);
  
  servo1.write(servo1Pos);
  servo2.write(servo2Pos);

  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 10000000;
  config.frame_size = FRAMESIZE_HD;
  /*
    FRAMESIZE_HD (1280 x 720)
    FRAMESIZE_QSXGA (2592 x 1944)
    FRAMESIZE_FHD (1920 x 1080)
    FRAMESIZE_UXGA (1600 x 1200)
    FRAMESIZE_QVGA (320 x 240)
    FRAMESIZE_CIF (352 x 288)
    FRAMESIZE_VGA (640 x 480)
    FRAMESIZE_SVGA (800 x 600)
    FRAMESIZE_XGA (1024 x 768)
    FRAMESIZE_SXGA (1280 x 1024)
  */
  config.pixel_format = PIXFORMAT_JPEG;
  config.grab_mode = CAMERA_GRAB_LATEST;
  config.fb_location = CAMERA_FB_IN_PSRAM;
  config.jpeg_quality = 10;
  config.fb_count = 2;

  //LED pin init
  pinMode(LED_GPIO_NUM, OUTPUT);

  // camera init
  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed with error 0x%x", err);
    return;
  }

  sensor_t * s = esp_camera_sensor_get();
  s->set_vflip(s, 1);

  WiFi.begin(ssid, password);
  WiFi.setSleep(false);

  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }
  Serial.println("");
  Serial.println("WiFi connected");

  // OTA setup
  ArduinoOTA.setHostname(OTA_HOSTNAME);
  
  // Set OTA password for secure firmware updates
  ArduinoOTA.setPassword(OTA_PASSWORD);
  // Alternatively, if you prefer to use a hash for added security:
  // ArduinoOTA.setPasswordHash("sha256_hashed_password_here");

  // Optional callbacks to report OTA update progress and error messages
  ArduinoOTA.onStart([]() {
    Serial.println("Start updating firmware via OTA...");
    otaInProgress = true;  // Pause WebSockets
  });
  ArduinoOTA.onEnd([]() {
    Serial.println("\nOTA update complete!");
    otaInProgress = false;
  });
  ArduinoOTA.onProgress([](unsigned int progress, unsigned int total) {
    Serial.printf("Progress: %u%%\r", (progress * 100 / total));
  });
  ArduinoOTA.onError([](ota_error_t error) {
    Serial.printf("OTA Error[%u]: ", error);
    if (error == OTA_AUTH_ERROR) Serial.println("Authentication Failed");
    else if (error == OTA_BEGIN_ERROR) Serial.println("Begin Failed");
    else if (error == OTA_CONNECT_ERROR) Serial.println("Connection Failed");
    else if (error == OTA_RECEIVE_ERROR) Serial.println("Reception Failed");
    else if (error == OTA_END_ERROR) Serial.println("Finalization Failed");
  });

  // Start OTA service
  ArduinoOTA.begin();
  Serial.println("OTA Ready!");

  // Print simple free heap info
  Serial.print("Free heap: ");
  Serial.println(ESP.getFreeHeap());
  
  // Print detailed heap information for 8-bit accessible memory:
  heap_caps_print_heap_info(MALLOC_CAP_8BIT);

  client.onEvent(onEventsCallback);
  client.onMessage(onMessageCallback);

  while(!isWebSocketConnected) {
    webSocketConnect();
  }
  udpSendMessage();
}

void captureTask(void * parameter) {
  // Assign a high priority to this task (e.g., 3; adjust as needed)
  const int capturePriority = uxTaskPriorityGet(NULL);
  Serial.print("[CaptureTask] Starting with priority: ");
  Serial.println(capturePriority);

  // Frame counter for diagnostics
  uint32_t frameCount = 0;

  while (true) {
    // Simulate capturing a frame (replace with your JPEG capture)
    // For instance, here you might read data from your camera, embed timestamp, etc.
    frameCount++;
    
    // Log the frame capture count occasionally (or send it to another monitoring system)
    if (frameCount % 10 == 0) {
      Serial.print("[CaptureTask] Frames captured so far: ");
      Serial.println(frameCount);
    }

    // Simulate frame processing delay. Replace with your actual capture logic.
    // Adjust the delay to mimic your desired FPS (7 fps ~ 140 ms per frame, etc.)
    vTaskDelay(pdMS_TO_TICKS(140));
  }
}

// This task periodically monitors the system's free heap, uptime, and other details.
// It can help reveal any gradual memory leaks or irregular task scheduling.
void monitorTask(void * parameter) {
  while (true) {
    // Get the free heap memory
    uint32_t freeHeap = ESP.getFreeHeap();

    // Get the running time in milliseconds
    uint32_t uptime = millis();

    // Optionally, adjust or log any task-specific priorities
    // Here, we just log the priority of this monitor task for diagnostics.
    UBaseType_t currentPriority = uxTaskPriorityGet(NULL);

    Serial.print("[MonitorTask] Uptime (ms): ");
    Serial.print(uptime);
    Serial.print(" | Free Heap: ");
    Serial.print(freeHeap);
    Serial.print(" | Monitor Task Priority: ");
    Serial.println(currentPriority);

    // Delay for a period (e.g., every 5 seconds) before checking again.
    vTaskDelay(pdMS_TO_TICKS(5000));
  }
}

void udpSendMessage() {
  sensors.setWaitForConversion(false);  // makes it async
  sensors.requestTemperatures();
  sensors.setWaitForConversion(true);
  
  int temperatureF = sensors.getTempFByIndex(0);
  String message_udp = "cam_one_temp_" + String(temperatureF);
  const char* udp_message = message_udp.c_str();

  udp.beginPacket(websocket_server_host, udp_port);
  udp.write((const uint8_t*)udp_message, strlen(udp_message));
  udp.endPacket();

  int esp32Temp = (temprature_sens_read() - 32) / 1.8;
  Serial.print("ESP32-CAM Chip Temperature: ");
  Serial.print(esp32Temp); // Convert Fahrenheit to Celsius
  Serial.println(" °C");
  message_udp = "cam_one_temp_int_" + String(esp32Temp);
  const char* udp_message_2 = message_udp.c_str();

  udp.beginPacket(websocket_server_host, udp_port);
  udp.write((const uint8_t*)udp_message_2, strlen(udp_message_2));
  udp.endPacket();
}

// --- JPEG validation function ---
// Checks the JPEG start (0xFF, 0xD8) and end (0xFF, 0xD9) markers.
bool isValidJPEG(const uint8_t* buf, size_t len) {
  if (len < 4) return false; // Too small to be a valid JPEG
  return (buf[0] == 0xFF && buf[1] == 0xD8 &&
          buf[len - 2] == 0xFF && buf[len - 1] == 0xD9);
}

void webSocketConnect() {
  while(!client.connect(websocket_server_host, websocket_server_port, "/")){
    Serial.print(".");
    delay(500);
  }
}

void onEventsCallback(WebsocketsEvent event, String data){
  if(event == WebsocketsEvent::ConnectionOpened){
    isWebSocketConnected = true;
    Serial.println("Connection Opened");
  } else if(event == WebsocketsEvent::ConnectionClosed){
    Serial.println("Connection Closed");
    isWebSocketConnected = false;
    // webSocketConnect();
    ESP.restart();
  }
}

void websocketSendPhoto() {
  if(client.available()){
    client.poll();
    client.onMessage(onMessageCallback);
  }

  if(!isWebSocketConnected) {
    ESP.restart();
  }

  camera_fb_t * fb = esp_camera_fb_get();

  if(!fb){
    Serial.println("Camera Capture Failed");
    esp_camera_fb_return(fb);
    return;
  }

  if(fb->format != PIXFORMAT_JPEG){
    Serial.println("Non-JPEG data not implemented");
    return;
  }

  // Validate JPEG data before sending
  if (isValidJPEG(fb->buf, fb->len)) {
    // Serial.printf("Valid JPEG captured (%d bytes)\n", fb->len);
    fb->buf[12] = HOST_IDENT; //Cam 1
    const char* frameBuff = (const char*) fb->buf;
    size_t frameLength = fb->len;

    client.sendBinary(frameBuff , frameLength);
  } else {
    Serial.println("Invalid JPEG data; frame skipped");
  }

  esp_camera_fb_return(fb);
  // delay(42);
}

void websocketSendTemp() {
  if(client.available()){
    client.poll();
    client.onMessage(onMessageCallback);
  }

  if(!isWebSocketConnected) {
    ESP.restart();
  }

  sensors.setWaitForConversion(false);  // makes it async
  sensors.requestTemperatures();
  sensors.setWaitForConversion(true);
  
  int temperatureF = sensors.getTempFByIndex(0);
  temp = "cam_one_temp_" + (String) temperatureF;
  client.send(temp);
}

void onMessageCallback(WebsocketsMessage message) {
  // Serial.println(message.data()+" "+message.data().length());
  String command = message.data();

  String cameraCheck = "ESP32-";
  cameraCheck.concat(cameraNum);
  
  if(command.indexOf(cameraCheck) != -1) {
    if(command.indexOf("Right") != -1 && servo2Pos >= 10) {
      servo2Pos -= 10;
      servo2.write(servo2Pos);
      client.send((String) servo2Pos);
    } else if(command.indexOf("Left") != -1 && servo2Pos <= 170) {
      servo2Pos += 10;
      servo2.write(servo2Pos);
      client.send((String) servo2Pos);
    } else if(command.indexOf("Down") != -1 && servo1Pos >= 10) {
      servo1Pos -= 10;
      servo1.write(servo1Pos);
      client.send((String) servo1Pos);
    } else if(command.indexOf("Up") != -1 && servo1Pos <= 170) {
      servo1Pos += 10;
      servo1.write(servo1Pos);
      client.send((String) servo1Pos);
    }
  }

  if(command.length() != 4) {
    Serial.println(command);
  }
}

void loop() {
  ArduinoOTA.handle();
  unsigned long currentMillis = millis();

  if(!otaInProgress) {
    websocketSendPhoto();
    if(currentMillis - previousMillis >= timerInterval) {
      previousMillis = millis();
      udpSendMessage();
      Serial.println("\n--- Heap Info ---");
      heap_caps_print_heap_info(MALLOC_CAP_8BIT);
    }
  }
}
