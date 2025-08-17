const path = require('path');
const express = require('express');
const WebSocket = require('ws');
const bodyParser = require('body-parser');
const fs = require('fs');
const cjson = require('compressed-json');
const { spawn } = require('child_process');
const dgram = require('dgram');
const mysql = require('mysql2');
const { PassThrough } = require("stream");
const phpExpress  = require('php-express')({
  binPath: 'php'    // path to your PHP CLI
});
const fetch = require('node-fetch');
const cors = require('cors');
const { JSDOM } = require('jsdom');
const { once } = require('events');

require('dotenv').config();

const WS_PORT = process.env.WS_PORT;
const HTTP_PORT = process.env.HTTP_PORT;
const WS_URL = process.env.WS_URL;
const requestKey = process.env.REQUEST_KEY;
const requestTemp = process.env.REQUEST_TEMP;
const requestVideo = process.env.REQUEST_VIDEO;

const timeToRestartVideo = 60/*seconds per minute*/ * 60/*minute per hour*/ * 1000/*milliseconds per second*/;
const encodingRestartPause = 100; //100 ms time before restarting ffmpeg encoding stream 

const db = mysql
  .createPool({
    host:     process.env.DB_HOST,
    user:     process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
  })
  .promise();

// Quick startup check
;(async function verifyDbConnection() {
  try {
    const conn = await db.getConnection();
    // Option 1: simple ping
    await conn.ping();
    // Option 2: lightweight query
    // await conn.query('SELECT 1');
    conn.release();
    console.log('✅ Database connection successful.');
  } catch (err) {
    console.error('❌ Unable to connect to database:', err);
    // Stop the process if DB is down
    process.exit(1);
  }
})();

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

const queryTemp = 'SELECT * FROM temperature ORDER BY timestamp DESC LIMIT 1';
const queryFood = 'SELECT * FROM food ORDER BY title COLLATE utf8mb4_general_ci ASC';

async function refreshTemperatureBuffer() {
  const [rows] = await db.query(queryTemp);
  if (rows.length > 0) {
    const { device_name, temperature } = rows[0];
    temperatureBuffer[device_name] = temperature;
  }
}

udpServer.on('message', async (msg, rinfo) => {
  const text = msg.toString();
  if (text.includes("int")) return;

  const cameraIdentifier = text.slice(0, 7);
  const temperature      = text.slice(text.lastIndexOf("_") + 1);

  const prevTemp = temperatureBuffer[cameraIdentifier];
  if (
    prevTemp !== undefined &&
    temperature !== prevTemp &&
    cameraIdentifier
  ) {
    const insertSql = 'INSERT INTO temperature (device_name, temperature) VALUES (?, ?)';
    try {
      await db.query(insertSql, [cameraIdentifier, temperature]);
      console.log(`Temperature ${temperature} logged in database.`);
      temperatureBuffer[cameraIdentifier] = temperature;
    } catch (err) {
      console.error('DB error on UDP insert:', err);
    }
  }

  broadcastMessage(text);
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
        jpegBufferQueue: [], // For buffered JPEG frames, if needed.
        baseTempFile,
        outputFile,
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
            }, 10000);
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
    baseTempFile = `${baseFilePath}.temp_${identifier}_${formattedDateTime}.mp4`;
    outputFile = `${baseFilePath}${identifier}_${formattedDateTime}.mp4`;
  } catch (err) {
    console.log(`${identifier}: Base directory is NOT writable, using backup.`);
    baseTempFile = `${secondFilePath}.temp_${identifier}_${formattedDateTime}.mp4`;
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

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Proxy running on http://localhost:${PORT}`));

app.listen(HTTP_PORT, ()=> console.log('HTTP server listening at '+HTTP_PORT));

app.use(express.static("."));
app.use(cors());
app.use(bodyParser.json());
// Register .php as a view engine
app.engine('php', phpExpress.engine);
app.set('views', path.join(__dirname, 'Routes'));
app.set('view engine', 'php');


// Route all .php files through the PHP engine
app.all(/.+\.php$/, phpExpress.router);

app.get('/recipes', (req, res) => {
  res.render('recipes');   // looks for Routes/recipes.php
});
app.get('/', (req, res) => {
  res.render('home');   // looks for Routes/home.php
});

// app.get('/', (req, res)=>res.sendFile(path.resolve(__dirname, './Routes/home.html')));
// app.get('/recipes', (req, res)=>res.sendFile(path.resolve(__dirname, './Routes/recipes.html')));

app.post('/config', async (req, res) => {
    try {
        if (req.body.requestKey !== requestKey) {
            return res.status(403).json({ error: 'Unauthorized request' });
        }
        res.json({ WS_URL });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
app.post('/temp', async (req, res) => {
  try {
    if (req.body.requestKey !== requestTemp) {
      return res.status(403).json({ error: 'Unauthorized request' });
    }

    // Refresh the buffer
    refreshTemperatureBuffer();

    //Send result
    res.json({ temperatureBuffer });
  } catch (error) {
    console.error('Server error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});
app.post('/save-video', async (req, res) => {
    try {
        if (req.body.requestKey !== requestVideo) {
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

app.post('/add-food', async (req, res) => {
    try {
        response = await upsertItemDB(req.body, 'food');
        res.json({ response });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
app.get('/get-food', async (req, res) => {
  try {
    const [foodItems] = await db.query(queryFood);
    res.json({ foodItems });
  } catch (error) {
    console.error('Server error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/add-recipe', async (req, res) => {
    let response = {};
    try {
        let keysToPick = ['title', 'servings', 'description'];

        const recipeJson = keysToPick.reduce((acc, key) => {
          if (key in req.body) {
            acc[key] = req.body[key];
          }
          return acc;
        }, {});

        keysToPick = ['title', 'step', 'instruction'];

        const instructionJson = (req.body.instruction || []).map(item => {
          return keysToPick.reduce((acc, key) => {
            if (key in item) {
              acc[key] = item[key];
            }
            return acc;
          }, {});
        });

        // console.log(recipeJson);
        // console.log(instructionJson);

        firstResponse = await upsertItemDB(recipeJson, 'recipe');
        secondResponse = await upsertItemDB(instructionJson, 'instruction');

        // console.log(firstResponse);
        // console.log(secondResponse);

        const recipeId = firstResponse.main.ids[0];
        const recipeInstructionJson = secondResponse.main.ids.map(instructionId => ({
            recipe_id: recipeId,
            instruction_id: instructionId
        }));

        const thirdResponse = await upsertRelationalItems(recipeInstructionJson, 'recipe_instruction', ['recipe_id', 'instruction_id']);

        const recipeFoodJson = (req.body.food || []).map(item => ({
            recipe_id: recipeId,
            food_id: item.food_id,
            amount: item.amount,
        }));

        const fourthResponse = await upsertRelationalItems(recipeFoodJson, 'recipe_food', ['recipe_id', 'food_id']);

        // console.log(thirdResponse);
        // console.log(fourthResponse);

        response.resultMessage = [...firstResponse.resultMessage, ...secondResponse.resultMessage, ...thirdResponse.resultMessage, ...fourthResponse.resultMessage].filter(msg => !msg.toLowerCase().includes('success'));
        if (response.resultMessage.length === 0) {
          response.resultMessage = [`${recipeJson.title} successfully added to recipes`];
        }
        
        res.json({ response });
    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ error: 'Internal server error', resultMessage: 'error' });
    }
});


app.get('/proxy', async (req, res) => {
  const { url } = req.query;
  try {
    new URL(url);
  } catch {
    return res.status(400).json({ error: "Invalid URL" });
  }

  let webscrape;

  if(url.includes("hannaford")) {
    webscrape = await fetchHannafordInfo(url);
    // console.log(webscrape.results);
    if(webscrape.success) {
        res.json(webscrape.results);
    }
    else {
        res.status(500).json({ error: "Error fetching or parsing content" });
    }
  }
});

/**
 * Insert one or more items into `tableName`. Optionally ensure and link
 * to a related table via `relationalName`, using data in item[relationalName]
 * or item.relatedId.
 *
 * @param  {Object|Object[]} items
 ]}
 * @param  {string}           tableName
 * @returns {string}        success message or fail message
 */
async function upsertItemDB(input, tableName) {
  const conn = await db.getConnection();

  try {
    await conn.beginTransaction();

    // Normalize to array
    const items = Array.isArray(input) ? input : [input];

    // Prepare one summary object
    const summary = {
      main: {
        ids: [],
        insertedCount: 0,
        updatedCount: 0
      },
      resultMessage: []
    };

    for (const item of items) {
      // 1. Look for an existing row by title
      const selectSql = `
        SELECT id
        FROM \`${tableName}\`
        WHERE title = ?
        LIMIT 1;
      `;
      const [rows] = await conn.query(selectSql, [item.title]);

      let id, message, result;

      if (rows.length > 0) {
        // 2a. If found → UPDATE
        id = rows[0].id;

        const colsToUpdate = Object.keys(item).filter(c => c !== 'id' && c !== 'title');
        const assignments  = colsToUpdate.map(c => `\`${c}\` = ?`).join(', ');
        const values       = colsToUpdate.map(c => item[c]);

        const updateSql = `
          UPDATE \`${tableName}\`
          SET ${assignments}
          WHERE id = ?;
        `;
        [result] = await conn.query(updateSql, [...values, id]);

        summary.main.updatedCount++;
        message = `Successfully updated ${item.title} in ${tableName}`;
      } else {
        // 2b. If not found → INSERT
        const cols         = Object.keys(item);
        const placeholders = cols.map(() => '?').join(', ');
        const values       = cols.map(c => item[c]);

        const insertSql = `
          INSERT INTO \`${tableName}\`
            (${cols.map(c => `\`${c}\``).join(',')})
          VALUES (${placeholders});
        `;
        [result] = await conn.query(insertSql, values);

        id = result.insertId;
        summary.main.insertedCount++;
        message = `Successfully inserted ${item.title} in ${tableName}`;
      }

      // Record ID and the message
      summary.main.ids.push(id);
      summary.resultMessage.push(message);
    }

    // Commit once for the whole batch
    await conn.commit();
    summary.resultMessage.forEach(message => {
        console.log(message);
    });
    return summary;
  } 
  catch (err) {
    await conn.rollback();
    console.error('Transaction failed:', err);
    throw err;
  } 
  finally {
    conn.release();
  }
}

/**
 * Inserts or updates rows in a pure‐relational (join) table,
 * using uniqueColumns to detect existing records.
 *
 * @param  {Object|Object[]} input         Single item or array of items to upsert.
 * @param  {string}         tableName     MySQL table to touch.
 * @param  {string[]}       uniqueColumns Columns whose combined values define uniqueness.
 * @returns {Promise<Object>}              { ids: [], insertedCount, updatedCount, messages: [] }
 */
async function upsertRelationalItems(input, tableName, uniqueColumns) {
  const conn = await db.getConnection();
  const items = Array.isArray(input) ? input : [input];

  // summary of everything we did
  const summary = {
    ids: [],
    insertedCount: 0,
    updatedCount: 0,
    resultMessage: []
  };

  try {
    await conn.beginTransaction();

    // build reusable WHERE clause e.g. "`parent_id` = ? AND `child_id` = ?"
    const whereClause = uniqueColumns.map(col => `\`${col}\` = ?`).join(' AND ');

    for (const item of items) {
      // 1) Collect the values for your unique columns in order
      const uniqueValues = uniqueColumns.map(col => item[col]);

      // 2) See if this record already exists
      const selectSql = `
        SELECT id
        FROM \`${tableName}\`
        WHERE ${whereClause}
        LIMIT 1;
      `;
      const [rows] = await conn.query(selectSql, uniqueValues);

      let id, sqlResult, msg;

      if (rows.length) {
        // → UPDATE
        id = rows[0].id;

        // pick only the cols that really need updating
        const colsToUpdate = Object.keys(item)
          .filter(col => col !== 'id' && !uniqueColumns.includes(col));

        if (colsToUpdate.length) {
          const assignments = colsToUpdate.map(col => `\`${col}\` = ?`).join(', ');
          const updateSql   = `
            UPDATE \`${tableName}\`
            SET ${assignments}
            WHERE ${whereClause};
          `;
          const values = colsToUpdate.map(col => item[col])
                        .concat(uniqueValues);

          [sqlResult] = await conn.query(updateSql, values);
        }

        summary.updatedCount++;
        msg = `Successfully updated row ${id} in ${tableName}`;
      } else {
        // → INSERT everything
        const cols         = Object.keys(item);
        const placeholders = cols.map(() => '?').join(', ');
        const insertSql    = `
          INSERT INTO \`${tableName}\`
            (${cols.map(c => `\`${c}\``).join(', ')})
          VALUES (${placeholders});
        `;
        const values = cols.map(c => item[c]);

        [sqlResult] = await conn.query(insertSql, values);
        id = sqlResult.insertId;

        summary.insertedCount++;
        msg = `Successfully inserted row ${id} in ${tableName}`;
      }

      summary.ids.push(id);
      summary.resultMessage.push(msg);
    }

    await conn.commit();
    summary.resultMessage.forEach(m => console.log(m));
    return summary;
  }
  catch (err) {
    await conn.rollback();
    console.error('Transaction failed:', err);
    throw err;
  }
  finally {
    conn.release();
  }
}

async function deleteItemDB(input, tableName) {
  const conn = await db.getConnection();

  try {
    await conn.beginTransaction();

    // Normalize to array
    const items = Array.isArray(input) ? input : [input];

    const summary = {
      main: {
        ids: [],
        deletedCount: 0
      },
      resultMessage: []
    };

    for (const item of items) {
      // 1. Look for an existing row by title
      const selectSql = `
        SELECT id
        FROM \`${tableName}\`
        WHERE title = ?
        LIMIT 1;
      `;
      const [rows] = await conn.query(selectSql, [item.title]);

      if (rows.length === 0) {
        summary.resultMessage.push(`No item found with title "${item.title}" in ${tableName}`);
        continue;
      }

      const id = rows[0].id;

      // 2. Delete the item by ID
      const deleteSql = `
        DELETE FROM \`${tableName}\`
        WHERE id = ?;
      `;
      const [result] = await conn.query(deleteSql, [id]);

      summary.main.ids.push(id);
      summary.main.deletedCount++;
      summary.resultMessage.push(`Successfully deleted "${item.title}" from ${tableName}`);
    }

    await conn.commit();
    summary.resultMessage.forEach(message => {
      console.log(message);
    });
    return summary;
  } catch (err) {
    await conn.rollback();
    console.error('Transaction failed:', err);
    throw err;
  } finally {
    conn.release();
  }
}

async function shutdownCamera(identifier) {
  const cam = cameras[identifier];
  const { ffmpegProcess: ff, baseTempFile, outputFile } = cam;
  if (!ff || ff.killed) return;

  cam.stream.unpipe(ff.stdin);
  ff.stdin.end();

  // wait for process to really exit
  const [code, signal] = await once(ff, 'exit');
  console.log(`FFmpeg for ${identifier} exited (code=${code}, signal=${signal})`);

  // perform rename immediately
  if (!fs.existsSync(baseTempFile)) {
    console.error(`No temp file ${baseTempFile} for ${identifier}`);
    return;
  }

  try {
    fs.renameSync(baseTempFile, outputFile);
    console.log(`Renamed to ${outputFile}`);
  } catch (err) {
    console.error(`Rename failed for ${identifier}:`, err);
  }
}

async function shutdownAllCameras() {
  for (const id of Object.keys(cameras)) {
    await shutdownCamera(id);
  }
}

async function gracefulShutdown() {
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
  db.end()
    .then(() => console.log('MySQL pool closed.'))
    .catch(err => console.error('Error closing pool:', err));

  await shutdownAllCameras();

  // Allow time for cleanup before exiting
  setTimeout(() => {
    console.log('Cleanup complete. Exiting.');
    process.exit(0);
  }, 1000);
}

async function fetchHannafordInfo(url) {
    try {
        const response = await fetch(url);
        const html = await response.text();
        const dom = new JSDOM(html);
        const nutritionDiv = dom.window.document.getElementById('primaryNutrition');
        const vitaminDiv = dom.window.document.getElementById('vitaminNutrition');
        const servingSizeDt = dom.window.document.querySelector('.serving-size dt');
        const servingSizeDd = dom.window.document.querySelector('.serving-size dd');
        const servingsPer = dom.window.document.querySelector('.servings-per');
        const productName = dom.window.document.querySelector('.product-name');
        const priceItem = dom.window.document.querySelector('.product-price');

        const servingInfo = {};
        const results = [];
        let priceValue = null;

        if(productName) {
            results.push({
                product: productName.textContent.trim(),
            })
        }

        if (servingsPer) {
            results.push({
                nutrient: "Total Servings",
                amount: servingsPer.textContent.trim(),
            });
        }

        if (servingSizeDt && servingSizeDd) {
            results.push({
                nutrient: servingSizeDt.textContent.trim(),
                amount: servingSizeDd.textContent.trim()
            });
        }

        if (priceItem) {
          const regularPrice = priceItem.querySelector('.price').textContent.trim();

          if (regularPrice) {
            results.push({
                nutrient: "Price",
                amount: regularPrice,
            });
          }
        }

        if (nutritionDiv) {
          const children = Array.from(nutritionDiv.children);

          children.forEach(child => {
            // Skip if this child is contained in vitaminNutrition
            if (vitaminDiv && vitaminDiv.contains(child)) return;

            const text = child.textContent.trim();
            if (!text) return;

            const nutrientMatch = text.match(/([\w\s]+)\n\s*(\d+[\d\.\w]*)\n\s*(\d+%)/);
            if (nutrientMatch) {
              results.push({
                nutrient: nutrientMatch[1].trim(),
                amount: nutrientMatch[2].trim(),
                // dailyValue: nutrientMatch[3].trim(),
              });
              return;
            }

            const simpleMatch = text.match(/([\w\s]+)\n\s*(\d+[\d\.\w]*)$/);
            if (simpleMatch) {
              results.push({
                nutrient: simpleMatch[1].trim(),
                amount: simpleMatch[2].trim(),
                // dailyValue: null,
              });
            }
          });
        }

        if (vitaminDiv) {
            const dlElements = vitaminDiv.querySelectorAll('dl');

            dlElements.forEach(dl => {
              const dt = dl.querySelector('dt');
              const dd = dl.querySelector('dd');

              if (dt && dd) {
                results.push({
                  nutrient: dt.textContent.trim(),
                  amount: dd.textContent.trim()
                });
              }
            });
        }
        return {
            'results': results,
            'success': true,
        };
      } catch (err) {
        return {
            'results': err.message,
            'success': false,
        };
      }
}

// Handle PM2 and keyboard interrupts
process.on('SIGINT', async () => {
  
  console.log('SIGINT received – starting cleanup');
  
  try {
    await gracefulShutdown();
    console.log('Cleanup complete. Exiting.');
    process.exit(0);
  } catch (err) {
    console.error('Error during shutdown:', err);
    process.exit(1);
  }
}); // Ctrl+C
process.on('SIGTERM', async () => {
  
  console.log('SIGTERM received – starting cleanup');
  
  try {
    await gracefulShutdown();
    console.log('Cleanup complete. Exiting.');
    process.exit(0);
  } catch (err) {
    console.error('Error during shutdown:', err);
    process.exit(1);
  }
}); // PM2 stop/restart