//TODO:
/*
* Add in functionality for recipes: adding food, adding units, adding dates to meals.
*/

const path = require('path');
const express = require('express');
const WebSocket = require('ws');
const bodyParser = require('body-parser');
const XMLHttpRequest = require("xhr2");
const fs = require('fs');
const cjson = require('compressed-json');
const { spawn } = require('child_process');
const dgram = require('dgram');
const mysql = require('mysql');
require('dotenv').config();

const WS_PORT = process.env.WS_PORT;
const HTTP_PORT = process.env.HTTP_PORT;
const WS_URL = process.env.WS_URL;

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
var lastSecond = String(now.getSeconds()).padStart(2, '0');

let connectedClients = [];
let webClients = [];
let bufferedMessage = "";
let lastTemperature = -100;
let lastTemperatureString = "";
let baseFilePath = process.env.SAVE_FILEPATH;

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

wsServer.on('connection', (ws, req)=>{
	const clientIP = req.socket.remoteAddress;
    console.log(`Client IP: ${clientIP} connected`);
	// connectedClients.push(ws);

	ws.on('message', data => {
		// console.log(ws.bufferedAmount)
		//If its a broswer
		if(data.indexOf("WEB_CLIENT") != -1) {
			webClients.push(ws);
			console.log("WEB_CLIENT ADDED");

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

			webClients.forEach((ws, i) => {
				if(webClients[i] == ws && ws.readyState === ws.OPEN){
					ws.send(lastTemperatureString);
				} else{
					webClients.splice(i, 1);
					console.log("WEB CLIENT DISCONNECTED")
				}
			});
		}

		webClients.forEach((ws, i) => {
			if(webClients[i] == ws && ws.readyState === ws.OPEN){
				ws.send(data);
			} else{
				webClients.splice(i, 1);
				console.log("WEB CLIENT DISCONNECTED")
			}
		});

		if (Buffer.isBuffer(data)) {  // Handle binary data (JPEG)
            // const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            now = new Date();
			year = now.getFullYear();
			month = String(now.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
			day = String(now.getDate()).padStart(2, '0');
			hours = String(now.getHours()).padStart(2, '0');
			minutes = String(now.getMinutes()).padStart(2, '0');
			seconds = String(now.getSeconds()).padStart(2, '0');
			milliseconds = String(now.getMilliseconds()).padStart(2, '0');

			formattedTime = `${minutes}-${seconds}-${milliseconds}`;
			formattedDate = `${year}-${month}-${day}-${hours}`;
			
            const filepath = baseFilePath + formattedDate + "/";
            
            if (fs.existsSync(filepath)) {
				const filename = `image-${formattedTime}.jpg`;
	            const fullpath = path.join(filepath, filename);

	            const outputStream = fs.createWriteStream(fullpath);
	            outputStream.write(data);
	            outputStream.end();

	            // console.log(`Saved image as ${fullpath}`);
			} else {
				fs.mkdirSync(filepath, { recursive: true });
  				console.log('Directory created successfully!');
			}
        } else {  // Handle non-binary data (Text)
            console.log(`Received WebSocket text message: ${data.toString()}`);
        }
	});

	ws.on("error", (error) => {
		console.error("Websocket error observed: ", error);
		ws.close();
	});

	ws.on("close", () => {
		console.log(`Client IP: ${clientIP} disconnected`)
	});
});

function broadcastMessage(message) {
    webClients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

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
app.listen(HTTP_PORT, ()=> console.log('HTTP server listening at '+HTTP_PORT));