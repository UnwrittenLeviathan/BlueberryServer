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
const timeToRestartVideo = 60/*seconds per minute*/ * 60/*minute per hour*/ * 1000/*milliseconds per second*/;
const encodingRestartPause = 100; //100 ms time before restarting ffmpeg encoding stream 

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
let temperatureBuffer = {};

let baseFilePath = process.env.SAVE_FILEPATH;
let secondFilePath = process.env.SAVE_FILEPATH_BACK;

const cameras = {};
let encodingShutdown = false;
let cameraIdentifierWS;

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
	    temperatureBuffer[results[0]['device_name']] = results[0]['temperature'];
	}
});

udpServer.on('message', (msg, rinfo) => {
    // console.log(msg.toString());
    if(msg.toString().indexOf("int") == -1) {
        temperature = msg.toString().substring(msg.toString().lastIndexOf("_")+1);
        cameraIdentifier = msg.toString().substring(0, 7);
        // console.log
        //Update for better error handling.
        if(temperatureBuffer[cameraIdentifier] !== 'undefined' && temperature != temperatureBuffer[cameraIdentifier] && temperature !== 'undefined' && cameraIdentifier !== 'undefined') {
        	const sql = 'INSERT INTO temperature (device_name, temperature) VALUES (?, ?)';
    	    db.query(sql, [cameraIdentifier, temperature], (err, result) => {
    	      if (err) throw err;
    	      console.log(`Temperature ${temperature} logged in database.`);
    	    });
        	temperatureBuffer[cameraIdentifier] = temperature;
        }
        broadcastMessage(msg.toString());
    }
});

udpServer.bind(9090, () => {
    console.log('UDP server listening on port 9090');
});

//Add functionality for more cameras, add var to pass into function.
function startEncoding(identifier) {
    const { baseTempFile, outputFile } = generateFileNames(identifier);

    const stream = new PassThrough();

    console.log(`Starting new FFmpeg encoding session: ${baseTempFile} for ${identifier}`);
    const ffmpegProcess = spawn("ffmpeg", [
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

  // Store or update the camera state, including the PassThrough stream.
    cameras[identifier] = {
        ffmpegProcess,
        stream, // The PassThrough stream is part of camera's state.
        encodingRestarting: false,
        restartTimer: null,
        jpegBufferQueue: [] // For buffered JPEG frames, if needed.
    };

    cameras[identifier].stream.pipe(ffmpegProcess.stdin);

    // Listen for FFmpeg progress output.
    cameras[identifier].ffmpegProcess.stdout.on("data", (data) => {
        const output = data.toString();
        if (!output.includes("continue")) {
            console.log(`FFmpeg Progress for ${identifier}: ${output}`);
        }
    });

  // Handle FFmpeg exit to rename files accordingly.
    cameras[identifier].ffmpegProcess.on("exit", (code, signal) => {
        if (code === 0) {
            console.log(`Encoding finished for ${identifier}. Renaming file...`);
            setTimeout(() => {
                if (!fs.existsSync(baseTempFile)) {
                    console.error(`Source file ${baseTempFile} not found for ${identifier}. Skipping rename.`);
                    return;
                }
                try {
                    fs.renameSync(baseTempFile, outputFile);
                    console.log(`File renamed for ${identifier}: ${outputFile}`);
                } catch (renameErr) {
                    console.error(`Error renaming file for ${identifier}:`, renameErr);
                }
            }, 1000);
        } else if (signal === "SIGINT" || signal === "SIGTERM") {
            console.log(`FFmpeg for ${identifier} exited with code ${code}, signal ${signal}.`);
            if (!fs.existsSync(baseTempFile)) {
                console.error(`Source file ${baseTempFile} not found for ${identifier}. Skipping rename.`);
                return;
            }
            try {
                fs.renameSync(baseTempFile, outputFile);
                console.log(`File renamed for ${identifier}: ${outputFile}`);
            } catch (renameErr) {
                console.error(`Error renaming file for ${identifier}:`, renameErr);
            }
        }
    });

  // Handle FFmpeg process errors.
    cameras[identifier].ffmpegProcess.on("error", (err) => {
        if (err.code === "EPIPE") {
            console.warn(`EPIPE error for ${identifier}: stream closed unexpectedly.`);
        }
        // End the stream and FFmpeg's stdin.
        stream.end();
        ffmpegProcess.stdin.end();
    });

    // Schedule an automatic restart for the camera's encoding session.
    cameras[identifier].restartTimer = setTimeout(() => restartEncoding(identifier), timeToRestartVideo);

    cameras[identifier].encodingRestarting = false;

    // Flush any buffered JPEG frames, if there are any.
    if (cameras[identifier].jpegBufferQueue.length > 0) {
        console.log(`Flushing ${cameras[identifier].jpegBufferQueue.length} buffered frames for ${identifier}...`);
        cameras[identifier].jpegBufferQueue.forEach((frame) => {
            if (ffmpegProcess.stdin.writable) {
                ffmpegProcess.stdin.write(frame);
            }
        });
        cameras[identifier].jpegBufferQueue = [];
    }
}

function restartEncoding(identifier) {
  const camera = cameras[identifier];
  if (!camera) {
    console.error(`Camera ${identifier} not found for restart.`);
    return;
  }
  console.log(`Restarting encoding for ${identifier}...`);
  camera.encodingRestarting = true;
  if (camera.ffmpegProcess) {
    camera.stream.end();
    camera.ffmpegProcess.stdin.end();
  }
  // Small delay to free up resources before restarting.
  setTimeout(() => {
    startEncoding(identifier);
  }, encodingRestartPause);
}

function checkCamera(identifier) {
  if (cameras[identifier]) {
    // console.error(`Camera ${identifier} already exists!`);
    return cameras[identifier];
  }
  console.log(`Adding new camera: ${identifier}`);
  startEncoding(identifier);
}

function removeCamera(identifier) {
  const camera = cameras[identifier];
  if (!camera) {
    console.error(`Camera ${identifier} not found for removal.`);
    return;
  }
  console.log(`Removing camera: ${identifier}`);
  
  // Clear the restart timer.
  if (camera.restartTimer) {
    clearTimeout(camera.restartTimer);
  }
  
  // Unpipe and destroy the PassThrough stream.
  camera.stream.unpipe();
  if (camera.stream.destroy) {
    camera.stream.destroy();
  }

  // Terminate the FFmpeg process.
  if (camera.ffmpegProcess) {
    camera.ffmpegProcess.kill("SIGTERM");
  }
  
  // Remove the camera from our global state.
  delete cameras[identifier];
}

function broadcastMessage(message) {
    webClients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

function isValidJPEG(buffer) {
    // JPEG files start with FF D8 and end with FF D9
    return buffer.slice(0, 2).toString('hex') === 'ffd8' &&
           buffer.slice(-2).toString('hex') === 'ffd9';
}

function repairJPEG(buffer) {
    const SOI = Buffer.from([0xFF, 0xD8]); // Start of Image
    const EOI = Buffer.from([0xFF, 0xD9]); // End of Image

    // Ensure buffer is large enough to contain valid JPEG markers
    if (buffer.length < 4) {
        console.log("Buffer too small to be a valid JPEG.");
        return null;
    }

    // Fix missing SOI marker
    if (buffer.slice(0, 2).toString('hex') !== 'ffd8') {
        console.log("Missing SOI marker, repairing...");
        buffer = Buffer.concat([SOI, buffer]);
    }

    // Fix missing EOI marker
    if (buffer.slice(-2).toString('hex') !== 'ffd9') {
        console.log("Missing EOI marker, repairing...");
        buffer = Buffer.concat([buffer, EOI]);
    }

    return buffer;
}

function generateFileNames(identifier) {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  const milliseconds = String(now.getMilliseconds()).padStart(3, '0');
  
  const formattedDateTime = `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
  let baseTempFile;
  let outputFile;

  try {
    // Test if baseFilePath is writable
    fs.accessSync(baseFilePath, fs.constants.W_OK);
    console.log(`${identifier}: Base directory is writable.`);
    baseTempFile = `${baseFilePath}.${identifier}_${formattedDateTime}.mp4`;
    outputFile = `${secondFilePath}${identifier}_${formattedDateTime}.mp4`;
  } catch (err) {
    console.log(`${identifier}: Base directory is NOT writable, using backup.`);
    baseTempFile = `${secondFilePath}.${identifier}_${formattedDateTime}.mp4`;
    outputFile = `${secondFilePath}${identifier}_${formattedDateTime}.mp4`;
  }
  return { baseTempFile, outputFile };
}

wsServer.on('connection', (ws, req)=>{
	const clientIP = req.socket.remoteAddress;
    console.log(`Client IP: ${clientIP} connected`);

	ws.on('message', data => {
		if(data.indexOf("WEB_CLIENT") != -1) {
			webClients.push(ws);
			console.log("WEB_CLIENT ADDED");
		} else if (Buffer.isBuffer(data)) {  // Handle binary data (JPEG)
            cameraIdentifierWS = "cam_"+data[11].toString();
            currCamera = checkCamera(cameraIdentifierWS);
            if (typeof currCamera == 'undefined') currCamera = checkCamera(cameraIdentifierWS);

            const buffer = Buffer.from(data);
            const validJPEG = isValidJPEG(buffer) ? buffer : repairJPEG(buffer);
            if (currCamera.encodingRestarting) {
                currCamera.jpegBufferQueue.push(validJPEG);
                console.log("Encoding restarting — buffering frame.");
            } else if (currCamera.ffmpegProcess?.stdin.writable) {
                currCamera.stream.write(validJPEG);
            } else if(!encodingShutdown) {
                console.error("Stream not writable — restarting encoding.");
                restartEncoding(cameraIdentifierWS);
            }
        } else {  // Handle non-binary data (Text)
            console.log(`Received WebSocket text message: ${data.toString()}`);
        }

        webClients = webClients.filter((client) => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(data);
                return true;
            } else {
                console.log("WEB CLIENT DISCONNECTED");
                return false;
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
                temperatureBuffer[results[0]['device_name']] = results[0]['temperature'];
                // console.log(temperatureBuffer);
            }
        });

        res.json({ temperatureBuffer });
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

        //Update in future for camera that is being restarted.
        restartEncoding(cameraIdentifierWS);
        response = "Successfully saved video";
        res.json({ response });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
app.listen(HTTP_PORT, ()=> console.log('HTTP server listening at '+HTTP_PORT));

function gracefulShutdown() {
  console.log('Shutting down gracefully...');
  encodingShutdown = true;

  // Close WebSocket server
  wsServer.close(() => {
    console.log('WebSocket server closed.');
  });

  // Close UDP server
  udpServer.close(() => {
    console.log('UDP server closed.');
  });

  // Close MySQL connection
  db.end((err) => {
    if (err) {
      console.error('Error closing MySQL connection:', err);
    } else {
      console.log('MySQL connection closed.');
    }
  });

  // Stop FFmpeg process if running
    Object.keys(cameras).forEach((identifier) => {
        // console.log(`Processing camera: ${identifier}`);
        if (cameras[identifier].ffmpegProcess && !cameras[identifier].ffmpegProcess.killed) {
            cameras[identifier].ffmpegProcess.kill('SIGINT');
            console.log('FFmpeg process terminated.');
          }
    });

  // Allow time for cleanup before exiting
  setTimeout(() => {
    console.log('Cleanup complete. Exiting.');
    process.exit(0);
  }, 1000);
}

// Handle PM2 and keyboard interrupts
process.on('SIGINT', gracefulShutdown);  // Ctrl+C
process.on('SIGTERM', gracefulShutdown); // PM2 stop/restart