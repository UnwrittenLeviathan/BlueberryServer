/**
 * @file Node.js server for handling WebSocket video streams, UDP temperature data,
 * database interactions for recipes and food, and a web scraping proxy.
 * @author [Your Name]
 * @date 2025-09-05
 */

// --- Core Dependencies ---
const path = require('path');
const fs = require('fs');
const { spawn } = require('child_process');
const dgram = require('dgram');
const { PassThrough } = require("stream");
const { once } = require('events');

// --- Frameworks & Libraries ---
const express = require('express');
const WebSocket = require('ws');
const mysql = require('mysql2');
const bodyParser = require('body-parser');
const cors = require('cors');
const { JSDOM } = require('jsdom');
const fetch = require('node-fetch');

// --- Environment & Configuration ---
require('dotenv').config();

const phpExpress = require('php-express')({
    binPath: 'php'
});

// --- Constants ---
const {
    WS_PORT,
    HTTP_PORT,
    PORT,
    WS_URL,
    DB_HOST,
    DB_USER,
    DB_PASSWORD,
    DB_DATABASE,
    REQUEST_KEY,
    REQUEST_TEMP,
    REQUEST_VIDEO,
    SAVE_FILEPATH,
    SAVE_FILEPATH_BACK
} = process.env;

const TIME_TO_RESTART_VIDEO_MS = 60 * 60 * 1000; // 1 hour in milliseconds
const ENCODING_RESTART_PAUSE_MS = 100; // Time to wait before restarting ffmpeg
const PONG_INTERVAL_MS = 10000;
let lastPongSent = 0;
const TIMEOUT_MS = 10000; // 10 seconds

// --- Global State ---
let webClients = [];
let temperatureBuffer = {};
const cameras = {};
let encodingShutdown = false;
let isShuttingDown = false;

// --- Database Setup ---
const db = mysql.createPool({
    host: DB_HOST,
    user: DB_USER,
    password: DB_PASSWORD,
    database: DB_DATABASE,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
}).promise();

// --- Server Instances ---
const app = express();
const wsServer = new WebSocket.Server({ port: WS_PORT }, () => {
    console.log(`[${new Date().toLocaleTimeString()}]`,`✅ WS Server is listening at ${WS_PORT}`);
});
const udpServer = dgram.createSocket('udp4');

// =============================================================================
// ++ INITIALIZATION
// =============================================================================

/**
 * Verifies the database connection on startup and exits if it fails.
 */
async function initialize() {
    try {
        const conn = await db.getConnection();
        await conn.ping();
        conn.release();
        console.log(`[${new Date().toLocaleTimeString()}]`,'✅ Database connection successful.');
    } catch (err) {
        console.error('❌ Unable to connect to database:', err);
        process.exit(1); // Exit if DB connection fails
    }

    // Start listening on all servers
    app.listen(HTTP_PORT, () => console.log(`[${new Date().toLocaleTimeString()}]`,`✅ HTTP server listening at ${HTTP_PORT}`));
    app.listen(PORT || 3000, () => console.log(`[${new Date().toLocaleTimeString()}]`,`✅ Proxy running on http://localhost:${PORT || 3000}`));
    udpServer.bind(9090, () => console.log(`[${new Date().toLocaleTimeString()}]`,'✅ UDP server listening on port 9090'));

    // Set up graceful shutdown handlers
    process.on('SIGINT', gracefulShutdown); // Ctrl+C
    process.on('SIGTERM', gracefulShutdown); // PM2 stop/restart
}

initialize();

// =============================================================================
// ++ MIDDLEWARE & ROUTING
// =============================================================================

app.use(express.static("."));
app.use(cors());
app.use(bodyParser.json());

// Set up PHP view engine
app.engine('php', phpExpress.engine);
app.set('views', path.join(__dirname, 'Routes'));
app.set('view engine', 'php');

// Route all .php files
app.all(/.+\.php$/, phpExpress.router);

// --- Page Routes ---
app.get('/', (req, res) => res.render('home'));
app.get('/recipes', (req, res) => res.render('recipes'));

// --- API Endpoints ---
app.post('/config', handleConfig);
app.post('/temp', handleTemp);
app.post('/add-food', handleAddFood);
app.get('/get-food', handleGetFood);
app.post('/add-recipe', handleAddRecipe);
app.get('/get-recipe', handleGetRecipe);
// app.get('/proxy', handleProxy);

// =============================================================================
// ++ WebSocket Handling
// =============================================================================


process.on('uncaughtException', (err) => {
    if (err.code === 'EPIPE') {
        console.warn(`[${new Date().toLocaleTimeString()}]`,"Global EPIPE caught. Likely writing to closed stream.");
    } else {
        console.error(`[${new Date().toLocaleTimeString()}]`,"Uncaught exception:", err);
    }
});

wsServer.on('connection', (ws, req) => {
    const clientIP = req.socket.remoteAddress;
    console.log(`[${new Date().toLocaleTimeString()}]`,`Client IP: ${clientIP} connected`);

    ws.on('message', (data) => handleWsMessage(ws, data));
    ws.on('error', (error) => {
        console.error(`[${new Date().toLocaleTimeString()}]`,"WebSocket error observed: ", error);
        ws.close();
    });
    ws.on('close', () => console.log(`[${new Date().toLocaleTimeString()}]`,`Client IP: ${clientIP} disconnected`));
});

/**
 * Handles incoming WebSocket messages, routing them based on type.
 * @param {WebSocket.WebSocket} ws The WebSocket client instance.
 * @param {Buffer|string} data The incoming message data.
 */
function handleWsMessage(ws, data) {
    const prefixLength = 4;

    if (!data || data.length < prefixLength) {
        console.warn(`[${new Date().toLocaleTimeString()}]`,"Invalid or too short data received");
        return;
    }

    try {
        let header = data.toString('utf8').slice(0, prefixLength);

        switch (header) {
            case "IMG:":
                const headerEnd = data.indexOf(':'); // Find colon
                const camIdChar = data.toString('utf8').charAt(headerEnd + 1); // ID after colon
                const camId = `cam_${camIdChar}`;
                const frameStart = headerEnd + 2; // JPEG starts after ID
                processVideoData(data.slice(frameStart), camId);

                // processVideoData(data.slice(prefixLength));
                break;
            case "JSN:":
                try {
                    let parsedMsg = JSON.parse(data.toString('utf8').slice(prefixLength));
                    handleStatusUpdate(parsedMsg);
                } catch (jsonErr) {
                    console.error(`[${new Date().toLocaleTimeString()}]`,"JSON parsing error:", jsonErr);
                }
                break;
            case "WEB_":
                webClients.push(ws);
                startClientLoop(ws);
                console.log(`[${new Date().toLocaleTimeString()}]`,"Web client added");
                break;
            case "ESP3":
                console.log(`[${new Date().toLocaleTimeString()}]`,"ESP32 client added");
                break;
            case "PONG":
                const sentTimestamp = parseInt(data.toString('utf8').slice(prefixLength));
                if (!isNaN(sentTimestamp)) {
                    const now = Date.now();
                    const latencyMs = now - sentTimestamp;
                    ws.latency = latencyMs+50;
                    ws.send(`LAT:${ws.latency}`);
                    // console.log(`📶 Round-trip latency from client: ${latency} ms`);
                } else {
                    console.warn(`[${new Date().toLocaleTimeString()}]`,"Invalid timestamp in PONG message");
                    console.log(`[${new Date().toLocaleTimeString()}]`,data.toString('utf8').slice(prefixLength));
                }
                break;
            default:
                console.log(`[${new Date().toLocaleTimeString()}]`,"Unhandled message:", data.toString("utf8"));
                break;
        }
    } catch (err) {
        console.error(`[${new Date().toLocaleTimeString()}]`,"Error in handleWsMessage:", err.stack || err);
    }
}
function startClientLoop(client) {
    client.lastSent = 0;
    client.lastPongSent = 0;

    const sendLoop = setInterval(() => {
        if (client.readyState !== WebSocket.OPEN) {
            clearInterval(sendLoop);
            return;
        }
        if (encodingShutdown) return;

        const now = Date.now();
        const interval = client.latency || 1000;

        if (!client.lastSent || now - client.lastSent >= interval) {
            // Iterate cameras asynchronously
            Object.keys(cameras).forEach((camId, idx) => {
                const cam = cameras[camId];
                if (!cam?.latestFrame) return;

                let header, framedData;
                if (!client.lastPongSent || now - client.lastPongSent >= PONG_INTERVAL_MS) {
                    const timestamp = Buffer.alloc(8);
                    timestamp.writeBigUInt64BE(BigInt(now));
                    header = Buffer.concat([Buffer.from("PONG"), timestamp]);
                    client.lastPongSent = now;
                } else {
                    header = Buffer.from(`IMG:`);
                    //Change this to be the camera ID as well after the colon
                }
                framedData = Buffer.concat([header, cam.latestFrame]);

                // Use async send with callback to avoid blocking
                client.send(framedData, (err) => {
                    if (err) {
                        if (err.code === 'EPIPE') {
                            console.warn(`[${new Date().toLocaleTimeString()}]`,
                                "EPIPE during WebSocket send. Client likely disconnected.");
                        } else {
                            console.error(`[${new Date().toLocaleTimeString()}]`,
                                "WebSocket send error:", err);
                        }
                        clearInterval(sendLoop);
                    }
                });

                client.lastSent = now;

                // Yield between cameras if many
                if (idx < Object.keys(cameras).length - 1) {
                    setImmediate(() => {}); // let event loop breathe
                }
            });
        }
    }, 500);
}

setInterval(() => {
  const now = Date.now();

  for (const [identifier, cam] of Object.entries(cameras)) {
    if (!cam) continue;

    // Skip if ffmpeg already exited
    if (!cam.ffmpegProcess || cam.ffmpegProcess.killed || cam.ffmpegProcess.exitCode !== null) {
      continue;
    }

    // Check last frame time
    if (now - cam.lastFrameTime > TIMEOUT_MS) {
      console.warn(`[${new Date().toLocaleTimeString()}]`,`${identifier}: No frames received for ${TIMEOUT_MS/1000}s, closing FFmpeg...`);
      try {
        removeCamera(identifier);
      } catch (e) {
        console.error(`[${new Date().toLocaleTimeString()}]`,`${identifier}: Error closing FFmpeg stdin:`, e);
      }
    }
  }
}, 1000);


/**
 * Handles status update from ESP32 client from Websocket connection.
 * @param {status} is the JSON formatted text which holds all the information.
 */
async function handleStatusUpdate(status) {
    const camId = `cam_${status.host_ident}`;
    const camera = checkCamera(camId);
    if (!camera) return;

    camera.lastStatus = status;
    broadcastStatus(camId, status);
    // console.log(`Status from ${camId}:`, status);

    const temperature = Math.round(status.dallasTempC * (9/5) + 32);
    const prevTemp = temperatureBuffer[camId];

    if (temperature !== prevTemp && camId) {
        const insertSql = 'INSERT INTO temperature (device_name, temperature) VALUES (?, ?)';
        try {
            await db.query(insertSql, [camId, temperature]);
            console.log(`[${new Date().toLocaleTimeString()}]`,`Temperature ${temperature} for ${camId} logged in database.`);
            temperatureBuffer[camId] = temperature;
        } catch (err) {
            console.error(`[${new Date().toLocaleTimeString()}]`,'DB error on UDP insert:', err);
        }
    }
}

function broadcastStatus(camId, status) {
    const payload = Buffer.from("JSN:" + JSON.stringify({ camId, status }));
    webClients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(payload, (err) => {
                if (err) {
                    console.error(`[${new Date().toLocaleTimeString()}]`, `WebSocket error sending status to client:`, err);
                }
            });
        }
    });
}

/**
 * Processes binary video data from a camera.
 * @param {Buffer} data The raw binary data (JPEG frame).
 */
function processVideoData(data, cameraIdentifier) {
    // const cameraIdentifier = `cam_${data[12].toString()}`;
    // console.log(cameraIdentifier);
    const currCamera = checkCamera(cameraIdentifier);
    if (!currCamera) return;

    const buffer = Buffer.from(data);
    const validJPEG = isValidJPEG(buffer) ? buffer : repairJPEG(buffer);

    if (validJPEG) {
        currCamera.latestFrame = validJPEG; // 🆕 Store the latest frame
        currCamera.lastFrameTime = Date.now();

        if (encodingShutdown) {
            console.log(`[${new Date().toLocaleTimeString()}]`,"System shutting down...");
            return;
        } else if (currCamera.ffmpegProcess?.stdin.writable) {
            try {
                currCamera.stream.write(validJPEG);
            } catch (err) {
                if (err.code === 'EPIPE') {
                    console.warn(`[${new Date().toLocaleTimeString()}]`,`Caught EPIPE while writing to ${cameraIdentifier}. Restarting encoding.`);
                    // restartEncoding(cameraIdentifier);
                } else {
                    console.error(`[${new Date().toLocaleTimeString()}]`,`Unexpected error while writing to stream:`, err);
                }
            }
        } else if (!encodingShutdown) {
            const { ffmpegProcess, stream } = currCamera;

            // Print diagnostic info
            console.error(
                `[${new Date().toLocaleTimeString()}]`,
                `Stream for ${cameraIdentifier} not writable. Details:`,
                {
                    streamDestroyed: stream.destroyed,
                    streamEnded: stream.writableEnded,
                    stdinDestroyed: ffmpegProcess?.stdin?.destroyed,
                    stdinWritable: ffmpegProcess?.stdin?.writable,
                    exitCode: ffmpegProcess?.exitCode,
                    killed: ffmpegProcess?.killed
                }
            );

            // Gracefully shut down this camera
            shutdownCamera(cameraIdentifier)
                .then(() => {
                    delete cameras[cameraIdentifier];
                    console.log(
                        `[${new Date().toLocaleTimeString()}]`,
                        `Camera ${cameraIdentifier} removed from cameras object.`
                    );
                })
                .catch(err => {
                    console.error(
                        `[${new Date().toLocaleTimeString()}]`,
                        `Error shutting down ${cameraIdentifier}:`,
                        err
                    );
                });
        }

    }
}

// =============================================================================
// ++ UDP Handling
// =============================================================================

// udpServer.on('message', async (msg) => {
//     const text = msg.toString();
//     if (text.includes("int")) return; // Ignore internal temperature readings

//     const cameraIdentifier = text.slice(0, 7);
//     const temperature = text.slice(text.lastIndexOf("_") + 1);

//     const prevTemp = temperatureBuffer[cameraIdentifier];
//     if (prevTemp !== undefined && temperature !== prevTemp && cameraIdentifier) {
//         const insertSql = 'INSERT INTO temperature (device_name, temperature) VALUES (?, ?)';
//         try {
//             await db.query(insertSql, [cameraIdentifier, temperature]);
//             console.log(`Temperature ${temperature} for ${cameraIdentifier} logged in database.`);
//             temperatureBuffer[cameraIdentifier] = temperature;
//         } catch (err) {
//             console.error('DB error on UDP insert:', err);
//         }
//     }

//     broadcastMessage(text);
// });

// =============================================================================
// ++ Camera & FFmpeg Management
// =============================================================================

/**
 * Ensures a camera's encoding process is running, starting it if necessary.
 * @param {string} identifier The unique identifier for the camera (e.g., "cam_1").
 * @returns {object|undefined} The camera state object or undefined if creation fails.
 */
function checkCamera(identifier) {
    if (cameras[identifier]) {
        return cameras[identifier];
    }
    console.log(`[${new Date().toLocaleTimeString()}]`,`Adding new camera: ${identifier}`);
    return startEncoding(identifier);
}

/**
 * Starts a new FFmpeg encoding session for a given camera.
 * @param {string} identifier The camera identifier.
 * @returns {object} The new camera state object.
 */
function startEncoding(identifier) {
    const stream = new PassThrough();

    let activePath = SAVE_FILEPATH;

    try {
        fs.accessSync(SAVE_FILEPATH, fs.constants.W_OK);
    } catch (err) {
        console.warn(`[${new Date().toLocaleTimeString()}]`,`${identifier}: Base directory not writable, using backup.`);
        activePath = SAVE_FILEPATH_BACK;
    }

    const outputFile = path.join(
        activePath,
        `${identifier}_%Y-%m-%d_%H-%M-%S.mp4`
      );


    stream.on('error', (err) => {
        if (err.code === 'EPIPE') {
            console.warn(`[${new Date().toLocaleTimeString()}]`,`EPIPE caught on stream for ${identifier}. Restarting encoding.`);
            // restartEncoding(identifier);
        } else {
            console.error(`[${new Date().toLocaleTimeString()}]`,`Stream error for ${identifier}:`, err);
        }
    });

    console.log(`[${new Date().toLocaleTimeString()}]`,`Starting FFmpeg stream: ${outputFile}`);
    const ffmpegProcess = spawn("ffmpeg", [
        "-f", "image2pipe",
        "-use_wallclock_as_timestamps", "1",
        "-fflags", "+genpts",
        "-i", "pipe:0",
        "-c:v", "h264_v4l2m2m",
        "-b:v", "5M",
        "-vf", `drawtext=fontfile=/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf:
                text='%{localtime}': x=10: y=10: fontsize=24: fontcolor=white: box=1: boxcolor=0x00000099,format=yuv420p`,
        "-progress", "-",
        "-nostats",
        "-f", "segment",
        "-segment_time", "3600",           // 1 hour per file
        "-reset_timestamps", "1",
        "-force_key_frames", "expr:gte(t,n_forced*10)",
        "-break_non_keyframes", "1",
        "-strftime", "1",                  // enable strftime in filenames
        outputFile,
    ]);

    cameras[identifier] = {
        ffmpegProcess,
        stream,
        encodingRestarting: false,
        restartTimer: null,
        jpegBufferQueue: [],
        latestFrame: null,
        lastFrameTime: Date.now(),
        outputFile,
    };

    stream.pipe(ffmpegProcess.stdin);

    ffmpegProcess.stdout.on("data", (data) => {
        const output = data.toString();
        if (!output.includes("continue")) {
            console.log(`[${new Date().toLocaleTimeString()}]`,`FFmpeg Progress (${identifier}): ${output}`);
        }
    });

    // ffmpegProcess.on("exit", (code, signal) => handleFFmpegExit(identifier, code, signal));
    ffmpegProcess.on("error", (err) => {
        if (err.code === "EPIPE") {
            console.warn(`[${new Date().toLocaleTimeString()}]`,`EPIPE error for ${identifier}: stream closed unexpectedly.`);
        }
        stream.end();
        ffmpegProcess.stdin.end();
    });

    // cameras[identifier].restartTimer = setTimeout(() => restartEncoding(identifier), TIME_TO_RESTART_VIDEO_MS);

    // Flush buffer if any frames were queued during restart
    flushJpegBuffer(identifier);

    return cameras[identifier];
}

async function removeCamera(identifier) {
  const cam = cameras[identifier];
  if (!cam) return;

  console.log(`🧹 Removing camera ${identifier}...`);
  // Prevent concurrent ops
  cam._removing = true;

  try { cam.stream?.unpipe?.(cam.ffmpegProcess?.stdin); } catch {}
  try { cam.ffmpegProcess?.stdin?.end?.(); } catch {}

  // Try to exit gracefully, then SIGTERM as fallback
  let exited = false;
  const p = (async () => {
    try { await once(cam.ffmpegProcess, 'exit'); exited = true; } catch {}
  })();

  // If not exited within ~1s, send SIGTERM
  setTimeout(() => {
    if (!exited && cam.ffmpegProcess && !cam.ffmpegProcess.killed) {
      try { cam.ffmpegProcess.kill('SIGTERM'); } catch {}
    }
  }, 1000);

  await p; // wait for exit (graceful or after SIGTERM)
  // Clear any camera-specific timers/queues
  try { clearTimeout(cam.restartTimer); } catch {}
  cam.jpegBufferQueue = [];

  delete cameras[identifier];
  console.log(`✅ Camera ${identifier} removed.`);
}

/**
 * Flushes any buffered JPEG frames to the FFmpeg process.
 * @param {string} identifier The camera identifier.
 */
function flushJpegBuffer(identifier) {
    const camera = cameras[identifier];
    if (!camera || camera.jpegBufferQueue.length === 0) return;

    console.log(`Flushing ${camera.jpegBufferQueue.length} buffered frames for ${identifier}...`);
    camera.jpegBufferQueue.forEach((frame) => {
        if (camera.ffmpegProcess.stdin.writable) {
            camera.ffmpegProcess.stdin.write(frame);
        }
    });
    camera.jpegBufferQueue = [];
}

// =============================================================================
// ++ API Route Handlers
// =============================================================================

async function handleConfig(req, res) {
    if (req.body.requestKey !== REQUEST_KEY) {
        return res.status(403).json({ error: 'Unauthorized request' });
    }
    res.json({ WS_URL });
}

async function handleTemp(req, res) {
    if (req.body.requestKey !== REQUEST_TEMP) {
        return res.status(403).json({ error: 'Unauthorized request' });
    }
    await refreshTemperatureBuffer();
    res.json({ temperatureBuffer });
}

async function handleAddFood(req, res) {
    try {
        const response = await upsertItemDB(req.body, 'food');
        res.json({ response });
    } catch (error) {
        console.error('Server error in /add-food:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
}

async function handleGetFood(req, res) {
    try {
        const [foodItems] = await db.query('SELECT * FROM food ORDER BY title COLLATE utf8mb4_general_ci ASC');
        res.json({ foodItems });
    } catch (error) {
        console.error('Server error in /get-food:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
}

async function handleAddRecipe(req, res) {
    try {
        const { title, servings, description, instruction: instructions, food } = req.body;

        // 1. Upsert Recipe and Instructions
        const recipeResult = await upsertItemDB({ title, servings, description }, 'recipe');
        const instructionResult = await upsertItemDB(instructions, 'instruction');
        const recipeId = recipeResult.main.ids[0];

        // 2. Link Instructions to Recipe
        const recipeInstructionLinks = instructionResult.main.ids.map(instructionId => ({
            recipe_id: recipeId,
            instruction_id: instructionId
        }));
        await upsertRelationalItems(recipeInstructionLinks, 'recipe_instruction', ['recipe_id', 'instruction_id']);

        // 3. Link Food to Recipe
        const recipeFoodLinks = food.map(item => ({
            recipe_id: recipeId,
            food_id: item.food_id,
            amount: item.amount,
            servings: item.servings
        }));
        await upsertRelationalItems(recipeFoodLinks, 'recipe_food', ['recipe_id', 'food_id']);

        res.json({ response: { resultMessage: [`${title} successfully added to recipes`] } });
    } catch (error) {
        console.error('Server error in /add-recipe:', error);
        res.status(500).json({ error: 'Internal server error', resultMessage: 'error' });
    }
}

async function handleGetRecipe(req, res) {
    try {
        const recipeJson = await fetchRecipesStructured();
        res.json({ recipeJson });
    } catch (err) {
        console.error('Error fetching recipes:', err);
        res.status(500).json({ error: 'Internal server error' });
    }
}

/*
async function handleProxy(req, res) {
    const { url } = req.query;
    try {
        new URL(url); // Validate URL format
    } catch {
        return res.status(400).json({ error: "Invalid URL" });
    }

    if (url.includes("hannaford")) {
        const webscrape = await fetchHannafordInfo(url);
        if (webscrape.success) {
            res.json(webscrape.results);
        } else {
            res.status(500).json({ error: "Error fetching or parsing content" });
        }
    } else {
        res.status(400).json({ error: "Proxy not supported for this URL" });
    }
}
*/

// =============================================================================
// ++ Database Utilities
// =============================================================================

/**
 * Inserts or updates items in a table based on a unique `title` field.
 * @param {object|object[]} input Single item or array of items.
 * @param {string} tableName The name of the table.
 * @returns {Promise<object>} A summary of the transaction.
 */
async function upsertItemDB(input, tableName) {
  const conn = await db.getConnection();
  const items = Array.isArray(input) ? input : [input];
  const summary = { main: { ids: [], insertedCount: 0, updatedCount: 0 }, resultMessage: [] };

  try {
    await conn.beginTransaction();

    for (const item of items) {
      let id;
      let rows = [];

      if (item.id) {
        // Prefer lookup by id if provided
        [rows] = await conn.query(`SELECT id FROM \`${tableName}\` WHERE id = ? LIMIT 1;`, [item.id]);
      } else if (item.title) {
        // Fallback: lookup by title
        [rows] = await conn.query(`SELECT id FROM \`${tableName}\` WHERE title = ? LIMIT 1;`, [item.title]);
      }

      if (rows.length > 0) {
        // Update existing
        id = rows[0].id;
        const colsToUpdate = Object.keys(item).filter(c => c !== 'id');
        if (colsToUpdate.length > 0) {
          const assignments = colsToUpdate.map(c => `\`${c}\` = ?`).join(', ');
          const values = colsToUpdate.map(c => item[c]);
          await conn.query(
            `UPDATE \`${tableName}\` SET ${assignments} WHERE id = ?;`,
            [...values, id]
          );
        }
        summary.main.updatedCount++;
        summary.resultMessage.push(`Successfully updated ${item.title || id} in ${tableName}`);
      } else {
        // Insert new
        const cols = Object.keys(item);
        const placeholders = cols.map(() => '?').join(', ');
        const values = cols.map(c => item[c]);
        const [result] = await conn.query(
          `INSERT INTO \`${tableName}\` (${cols.map(c => `\`${c}\``).join(',')}) VALUES (${placeholders});`,
          values
        );
        id = result.insertId;
        summary.main.insertedCount++;
        summary.resultMessage.push(`Successfully inserted ${item.title || id} in ${tableName}`);
      }

      summary.main.ids.push(id);
    }

    await conn.commit();
    summary.resultMessage.forEach(message => console.log(message));
    return summary;
  } catch (err) {
    await conn.rollback();
    console.error(`Transaction failed in upsertItemDB for table ${tableName}:`, err);
    throw err;
  } finally {
    conn.release();
  }
}

/**
 * Inserts or updates rows in a relational join table.
 * @param {object|object[]} input Single item or array of items.
 * @param {string} tableName The name of the join table.
 * @param {string[]} uniqueColumns An array of column names that define a unique record.
 * @returns {Promise<object>} A summary of the transaction.
 */
async function upsertRelationalItems(input, tableName, uniqueColumns) {
    const conn = await db.getConnection();
    const items = Array.isArray(input) ? input : [input];
    const summary = { ids: [], insertedCount: 0, updatedCount: 0, resultMessage: [] };

    try {
        await conn.beginTransaction();
        const whereClause = uniqueColumns.map(col => `\`${col}\` = ?`).join(' AND ');

        for (const item of items) {
            const uniqueValues = uniqueColumns.map(col => item[col]);
            const [rows] = await conn.query(`SELECT id FROM \`${tableName}\` WHERE ${whereClause} LIMIT 1;`, uniqueValues);
            let id;

            if (rows.length) { // Update
                id = rows[0].id;
                const colsToUpdate = Object.keys(item).filter(col => col !== 'id' && !uniqueColumns.includes(col));
                if (colsToUpdate.length) {
                    const assignments = colsToUpdate.map(col => `\`${col}\` = ?`).join(', ');
                    const values = [...colsToUpdate.map(col => item[col]), ...uniqueValues];
                    await conn.query(`UPDATE \`${tableName}\` SET ${assignments} WHERE id = ?;`, [...values, id]);
                }
                summary.updatedCount++;
                summary.resultMessage.push(`Successfully updated row ${id} in ${tableName}`);
            } else { // Insert
                const cols = Object.keys(item);
                const placeholders = cols.map(() => '?').join(', ');
                const values = cols.map(c => item[c]);
                const [result] = await conn.query(`INSERT INTO \`${tableName}\` (${cols.map(c => `\`${c}\``).join(', ')}) VALUES (${placeholders});`, values);
                id = result.insertId;
                summary.insertedCount++;
                summary.resultMessage.push(`Successfully inserted row ${id} in ${tableName}`);
            }
            summary.ids.push(id);
        }
        await conn.commit();
        summary.resultMessage.forEach(m => console.log(m));
        return summary;
    } catch (err) {
        await conn.rollback();
        console.error(`Transaction failed in upsertRelationalItems for table ${tableName}:`, err);
        throw err;
    } finally {
        conn.release();
    }
}

/**
 * Fetches all recipes and structures them into a nested JSON object.
 * @returns {Promise<object[]>} An array of structured recipe objects.
 */
async function fetchRecipesStructured() {
    const [rows] = await db.query(`
      SELECT
        r.id AS recipe_id, r.title AS recipe_title, r.servings AS recipe_servings, r.description AS recipe_description,
        rf.food_id, rf.amount AS food_amount, rf.servings AS food_servings, f.title AS food_title,
        i.title AS instr_title, i.step AS instr_step, i.instruction AS instr_text
      FROM recipe AS r
      LEFT JOIN recipe_food AS rf ON r.id = rf.recipe_id
      LEFT JOIN food AS f ON rf.food_id = f.id
      LEFT JOIN recipe_instruction AS ri ON r.id = ri.recipe_id
      LEFT JOIN instruction AS i ON ri.instruction_id = i.id
      ORDER BY r.id, rf.food_id, i.step;
    `);

    const recipeMap = new Map();
    for (const row of rows) {
        if (!recipeMap.has(row.recipe_id)) {
            recipeMap.set(row.recipe_id, {
                title: row.recipe_title,
                servings: row.recipe_servings,
                description: row.recipe_description,
                food: [],
                instruction: [],
                _seenFood: new Set(),
                _seenInstr: new Set()
            });
        }
        const recipe = recipeMap.get(row.recipe_id);

        if (row.food_id !== null && !recipe._seenFood.has(row.food_id)) {
            recipe._seenFood.add(row.food_id);
            recipe.food.push({
                food_id: row.food_id,
                amount: row.food_amount,
                servings: row.food_servings,
                title: row.food_title
            });
        }

        if (row.instr_title !== null && !recipe._seenInstr.has(row.instr_title)) {
            recipe._seenInstr.add(row.instr_title);
            recipe.instruction.push({
                title: row.instr_title,
                step: row.instr_step,
                instruction: row.instr_text
            });
        }
    }

    // Return a clean array without helper properties
    return Array.from(recipeMap.values()).map(r => {
        const { title, servings, description, food, instruction } = r;
        return { title, servings, description, food, instruction };
    });
}

// =============================================================================
// ++ Web Scraping Utilities
// =============================================================================

// None because of updates to hannaford website... sigh...

// =============================================================================
// ++ Helper Utilities
// =============================================================================

/**
 * Sends a message to all connected web clients.
 * @param {string} message The message to broadcast.
 */
function broadcastMessage(message) {
    webClients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

/**
 * Checks if a buffer contains a valid JPEG signature.
 * @param {Buffer} buffer The buffer to check.
 * @returns {boolean} True if the buffer is a valid JPEG.
 */
function isValidJPEG(buffer) {
    return buffer.length > 4 && buffer.slice(0, 2).toString('hex') === 'ffd8' && buffer.slice(-2).toString('hex') === 'ffd9';
}

/**
 * Attempts to repair a buffer that is missing JPEG start/end markers.
 * @param {Buffer} buffer The buffer to repair.
 * @returns {Buffer|null} The repaired buffer or null if too small.
 */
function repairJPEG(buffer) {
    if (buffer.length < 4) {
        console.warn(`[${new Date().toLocaleTimeString()}]`,"Buffer too small to repair as JPEG.");
        return null;
    }
    if (buffer.slice(0, 2).toString('hex') !== 'ffd8') {
        console.log(`[${new Date().toLocaleTimeString()}]`,"Missing SOI marker, repairing...");
        buffer = Buffer.concat([Buffer.from([0xFF, 0xD8]), buffer]);
    }
    if (buffer.slice(-2).toString('hex') !== 'ffd9') {
        console.log(`[${new Date().toLocaleTimeString()}]`,"Missing EOI marker, repairing...");
        buffer = Buffer.concat([buffer, Buffer.from([0xFF, 0xD9])]);
    }
    return buffer;
}


/**
 * Refreshes the in-memory temperature buffer from the database.
 */
async function refreshTemperatureBuffer() {
    try {
        const [rows] = await db.query('SELECT device_name, temperature FROM temperature ORDER BY timestamp DESC LIMIT 1');
        if (rows.length > 0) {
            const { device_name, temperature } = rows[0];
            temperatureBuffer[device_name] = temperature;
        }
    } catch (error) {
        console.error(`[${new Date().toLocaleTimeString()}]`,"Failed to refresh temperature buffer:", error);
    }
}

// =============================================================================
// ++ Graceful Shutdown Logic
// =============================================================================

/**
 * Shuts down a single camera's FFmpeg process and renames the file.
 * @param {string} identifier The camera identifier.
 */
async function shutdownCamera(identifier) {
    const cam = cameras[identifier];
    if (!cam || !cam.ffmpegProcess || cam.ffmpegProcess.killed) return;

    const { ffmpegProcess, outputFile, stream } = cam;
    console.log(`[${new Date().toLocaleTimeString()}]`,`Shutting down camera ${identifier}...`);
    
    stream.unpipe(ffmpegProcess.stdin);
    ffmpegProcess.stdin.end();

    const [code, signal] = await once(ffmpegProcess, 'exit');
    console.log(`[${new Date().toLocaleTimeString()}]`,`FFmpeg for ${identifier} exited (code=${code}, signal=${signal})`);
}

/**
 * Gracefully shuts down all services.
 */
async function gracefulShutdown() {
    // If we are already shutting down, do nothing.
    if (isShuttingDown) {
        return;
    }
    // Set the flag to true immediately to prevent re-entry.
    isShuttingDown = true;

    console.log(`[${new Date().toLocaleTimeString()}]`,'Shutting down gracefully...');
    encodingShutdown = true;

    // 1. Shut down all camera processes
    await Promise.all(Object.keys(cameras).map(id => shutdownCamera(id)));

    wsServer.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.close(1001, "Server shutting down"); // 1001 = Going Away
        }
    });

    // 2. Close servers
    wsServer.close(() => console.log(`[${new Date().toLocaleTimeString()}]`,'WebSocket server closed.'));
    udpServer.close(() => console.log(`[${new Date().toLocaleTimeString()}]`,'UDP server closed.'));

    // 3. Close database pool
    try {
        await db.end();
        console.log(`[${new Date().toLocaleTimeString()}]`,'MySQL pool closed.');
    } catch (err) {
        console.error(`[${new Date().toLocaleTimeString()}]`,'Error closing MySQL pool:', err);
    }

    // 4. Exit process
    setTimeout(() => {
        console.log(`[${new Date().toLocaleTimeString()}]`,'Cleanup complete. Exiting.');
        process.exit(0);
    }, 1000);
}