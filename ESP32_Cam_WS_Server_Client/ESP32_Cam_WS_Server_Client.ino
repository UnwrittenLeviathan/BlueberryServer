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

#define PART_BOUNDARY "123456789000000000000987654321"

#define SERVO_1      14
#define SERVO_2      15

#define SERVO_STEP   5

Servo servo1;
Servo servo2;

int servo1Pos = 90;
int servo2Pos = 90;

int cameraNum = 1;
String temp;

const char* ssid = "Epson H-1251";
const char* password = "merEGS2018";
const char* websocket_server_host = "192.168.1.5";
const uint16_t websocket_server_port = 8888;
const int udp_port = 9090;

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
  config.pixel_format = PIXFORMAT_JPEG;
  config.grab_mode = CAMERA_GRAB_LATEST;
  config.fb_location = CAMERA_FB_IN_PSRAM;
  config.jpeg_quality = 12;
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

  client.onEvent(onEventsCallback);
  client.onMessage(onMessageCallback);

  while(!isWebSocketConnected) {
    webSocketConnect();
  }
  udpSendMessage();
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

  Serial.print("ESP32-CAM Chip Temperature: ");
  Serial.print((temprature_sens_read() - 32) / 1.8); // Convert Fahrenheit to Celsius
  Serial.println(" °C");
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

  fb->buf[12] = 0x01; //Cam 1

  const char* frameBuff = (const char*) fb->buf;
  size_t frameLength = fb->len;

  client.sendBinary(frameBuff , frameLength);
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
  unsigned long currentMillis = millis();
  websocketSendPhoto();
  if(currentMillis - previousMillis >= timerInterval) {
    previousMillis = millis();
    udpSendMessage();
  }
}
