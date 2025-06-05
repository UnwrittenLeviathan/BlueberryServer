const path = require('path');
const express = require('express');
const WebSocket = require('ws');
const bodyParser = require('body-parser');
const fs = require('fs');
const cjson = require('compressed-json');
const { spawn } = require('child_process');
const dgram = require('dgram');
const mysql = require('mysql');
const { PassThrough } = require("stream");

require('dotenv').config();

const WS_PORT = process.env.WS_PORT;
const HTTP_PORT = process.env.HTTP_PORT;
const WS_URL = process.env.WS_URL;
const timeToRestartVideo = 60/*seconds per minute*/ * 30/*minute per hour*/ * 1000/*milliseconds per second*/;

const db = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_DATABASE 
});

const wsServer = new WebSocket.Server({port: WS_PORT}, ()=> console.log('WS Server is listening at '+WS_PORT));
const udpServer = dgram.createSocket('udp4');
const app = express();

var now = new Date();
var year = now.getFullYear();
var month = String(now.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
var day = String(now.getDate()).padStart(2, '0');
var hours = String(now.getHours()).padStart(2, '0');
var minutes = String(now.getMinutes()).padStart(2, '0');
var seconds = String(now.getSeconds()).padStart(2, '0');
var milliseconds = String(now.getMilliseconds()).padStart(2, '0');
var formattedDateTime = `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;

let connectedClients = [];
let webClients = [];
let bufferedMessage = "";
let lastTemperature = -100;
let lastTemperatureString = "";
let baseFilePath = process.env.SAVE_FILEPATH;
let stream = new PassThrough(); // Buffered pipeline for efficiency
let ffmpegProcess;
let restartTimer;
let encodingStarted = false; // Flag to track encoding state

db.connect((err) => {
  if (err) throw err;
  console.log('Connected to MySQL');
});

const queryTemp = 'SELECT * FROM temperature ORDER BY timestamp DESC LIMIT 1';
db.query(queryTemp, (err, results) => {
    if (err) {
        console.error('Error fetching data:', err);
        return;
    }
    if (typeof results[0] !== "undefined") {
	    lastTemperatureString = results[0]['device_name']+"_temp_"+results[0]['temperature']
    	lastTemperature = results[0]['temperature']
	}
});

udpServer.on('message', (msg, rinfo) => {
    // console.log(`Received UDP uint8_t message: ${msg.toString()} from ${rinfo.address}:${rinfo.port}`);
    temperature = msg.toString().substring(msg.toString().lastIndexOf("_")+1);
    cameraIdentifier = msg.toString().substring(0, 7);
    if(temperature == lastTemperature) {
    	console.log(`Last temp and new temp are same, not logged.`)
    } else {
    	const sql = 'INSERT INTO temperature (device_name, temperature) VALUES (?, ?)';
	    db.query(sql, [cameraIdentifier, temperature], (err, result) => {
	      if (err) throw err;
	      console.log(`Temperature of ${temperature} logged in database.`);
	    });
    	lastTemperature = temperature;
    }
    broadcastMessage(msg.toString());
});

udpServer.bind(9090, () => {
    console.log('UDP server listening on port 9090');
});

function startEncoding() {
	now = new Date();
	year = now.getFullYear();
	month = String(now.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
	day = String(now.getDate()).padStart(2, '0');
	hours = String(now.getHours()).padStart(2, '0');
	minutes = String(now.getMinutes()).padStart(2, '0');
	seconds = String(now.getSeconds()).padStart(2, '0');
	milliseconds = String(now.getMilliseconds()).padStart(2, '0');
    formattedDateTime = `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
    const baseTempFile = `${baseFilePath}`+`.${formattedDateTime}.mp4`;
    const outputFile = `${baseFilePath}`+`${formattedDateTime}.mp4`;

    console.log(`Starting new FFmpeg encoding session: ${baseTempFile}`);

    ffmpegProcess = spawn("ffmpeg", [
    	"-f", "image2pipe",
        "-framerate", "7",
        "-i", "pipe:0",
        "-c:v", "h264_v4l2m2m",
        "-b:v", "5M",  // Matches ESP32-CAM bitrate
        "-vf", "format=yuv420p", // Fix deprecated pixel format issue
        "-progress", "-", // Enables real-time progress output
    	"-nostats", // Suppresses unnecessary logs
        baseTempFile,
    ]);

    ffmpegProcess.stdout.on("data", (data) => {
        const output = data.toString();
        if(output.indexOf('continue') == -1) {
            console.log("FFmpeg Progress:", output);
        }
    });

    ffmpegProcess.on('close', (code) => {
      if (code === 0) {
        console.log('Encoding finished. Renaming file...');
        fs.rename(baseTempFile, outputFile, (err) => {
          if (err) {
            console.error('Error renaming file:', err);
          } else {
            console.log('File renamed successfully:', outputFile);
          }
        });
      } else {
        console.error('FFmpeg process exited with code:', code);
      }
    });


    ffmpegProcess.on("error", (err) => {
        if (err.code === "EPIPE") {
            console.warn("EPIPE detected: Stream closed unexpectedly.");
            stream.end(); // Close the stream safely
            ffmpegProcess.stdin.end();
        }
    });

    // **Monitor FFmpeg process for unexpected exit**
    ffmpegProcess.on("exit", (code, signal) => {
        if(code != 0) {
            console.log(`FFmpeg exited unexpectedly with code ${code}, signal ${signal}`);
        }
        stream.end(); // Close the stream safely
        ffmpegProcess.stdin.end();
    });

    // **Schedule hourly restart**
    restartTimer = setTimeout(restartEncoding, timeToRestartVideo); // 10 minutes

    // Pipe buffered stream into FFmpeg
    stream.pipe(ffmpegProcess.stdin);
}

if (!encodingStarted) {
    console.log("Received first WebSocket frame, starting FFmpeg...");
    startEncoding();
    encodingStarted = true;
}

function isValidJPEG(buffer) {
    // JPEG files start with FF D8 and end with FF D9
    return buffer.slice(0, 2).toString('hex') === 'ffd8' &&
           buffer.slice(-2).toString('hex') === 'ffd9';
}

// **Function to restart FFmpeg gracefully**
function restartEncoding() {
    console.log("Restarting FFmpeg encoding...");
    
    clearTimeout(restartTimer); // Cancel previous timer
    stream.end();
    ffmpegProcess.stdin.end();
    
    stream = new PassThrough(); // Reset buffered stream
    startEncoding();  // Restart FFmpeg
}

function broadcastMessage(message) {
    webClients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

wsServer.on('connection', (ws, req)=>{
	const clientIP = req.socket.remoteAddress;
    console.log(`Client IP: ${clientIP} connected`);
	// connectedClients.push(ws);

	ws.on('message', data => {
		if(data.indexOf("WEB_CLIENT") != -1) {
			webClients.push(ws);
			console.log("WEB_CLIENT ADDED");
		} else if (Buffer.isBuffer(data)) {  // Handle binary data (JPEG)
            const buffer = Buffer.from(data);
            if (isValidJPEG(buffer)) {
                if (ffmpegProcess?.stdin.writable) {
                    stream.write(buffer); // Write incoming frame to FFmpeg
                    // console.log("Writing buffer")
                } else {
                    console.log("Buffer not writable");
                    restartEncoding();
                }
            } else {
                console.error('Invalid JPEG data detected!');
                console.log(data.toString())
            }
        } else {  // Handle non-binary data (Text)
            console.log(`Received WebSocket text message: ${data.toString()}`);
        }

		webClients.forEach((ws, i) => {
			if(webClients[i] == ws && ws.readyState === ws.OPEN){
				ws.send(data);
			} else{
				webClients.splice(i, 1);
				console.log("WEB CLIENT DISCONNECTED")
			}
		});
	});

	ws.on("error", (error) => {
		console.error("Websocket error observed: ", error);
		ws.close();
	});

	ws.on("close", () => {
		console.log(`Client IP: ${clientIP} disconnected`)
	});
});

app.use(express.static("."));
app.use(bodyParser.json());
app.get('/', (req, res)=>res.sendFile(path.resolve(__dirname, './client.html')));
app.post('/config', async (req, res) => {
    try {
        if (req.body.requestKey !== 'my-client-request') {
            return res.status(403).json({ error: 'Unauthorized request' });
        }

        // Simulating an async operation (e.g., database query)
        await new Promise(resolve => setTimeout(resolve, 500)); // Simulated delay

        res.json({ WS_URL });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
app.post('/temp', async (req, res) => {
    try {
        if (req.body.requestKey !== 'my-client-request-temp') {
            return res.status(403).json({ error: 'Unauthorized request' });
        }

        // Simulating an async operation (e.g., database query)
        await new Promise(resolve => setTimeout(resolve, 500)); // Simulated delay

        db.query(queryTemp, (err, results) => {
            if (err) {
                console.error('Error fetching data:', err);
                return;
            }
            if (typeof results[0] !== "undefined") {
                lastTemperatureString = results[0]['device_name']+"_temp_"+results[0]['temperature'];
            }
        });

        res.json({ lastTemperatureString });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
app.post('/save-video', async (req, res) => {
    try {
        if (req.body.requestKey !== 'my-client-request-save-video') {
            return res.status(403).json({ error: 'Unauthorized request' });
        }

        // Simulating an async operation (e.g., database query)
        await new Promise(resolve => setTimeout(resolve, 500)); // Simulated delay

        restartEncoding();
        response = "Successfully saved video";
        res.json({ response });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
app.listen(HTTP_PORT, ()=> console.log('HTTP server listening at '+HTTP_PORT));