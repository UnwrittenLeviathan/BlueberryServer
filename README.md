# ESP32-RaspberryPi-Server

***

This project aims to use an Ai Thinker ESP32 camera, with the basic sensor (OV2640) to provide a real-time stream of images to both: html clients and an ffmpeg stream for recording the images received.

***

## ESP32 Camera

***

Using Arduino IDE installed with *insert libraries here*, and the Ai Thinker ESP32 cam selected as the board.
The code uses a *insert name of temp sensor* connected ot pin 2, and wired according to their specifications.
The design is to rapidly capture images on the ESP32 camera, send them via websockets to a server running on a raspberry pi 4*insert model*. The ESP32 device also intermittently captures it's own internal temperature, it's free memory, along with other data logging statistics to ensure no memory leak is present. These measurements are only logged to it's console. The temperature it reads from the external temperature sensor is read every 30 seconds and transmitted via UDP to the same raspberry pi server. The sensor is in low fidelity mode such that it is asynchronous and won't interfere with the sending of frame data while being read. It is sent via UDP to simplify the code on the server to not have to handle different types of binary data.

***

## Raspberry Pi 4 *insert model and such*

***

The raspberry pi 4 *insert model* acts as an HTTP, UDP, and Websocket Server. The HTTP server is only to host phpmyadmin. The UDP server is to handle incoming String messages. The websocket server handles incoming JPEG Binary data and forwards it to client.html using express and to ffmpeg encoding for recording of the data.