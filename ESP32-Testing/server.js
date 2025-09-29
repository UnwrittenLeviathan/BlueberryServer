const express = require('express');
const http = require('http');
const { WebSocketServer } = require('ws');
const path = require('path');

// --- Configuration ---
const WEB_PORT = 8080;

// --- Server Setup ---
const app = express();
const httpServer = http.createServer(app);
const wss = new WebSocketServer({ server: httpServer });

let espClient = null;
const browserClients = new Set();
let frameCount = 0;
let totalBytes = 0;

// --- WebSocket Server Logic ---

wss.on('connection', (ws, req) => {
    // The ESP32 will connect to this specific path.
    if (req.url === '/esp32-stream') {
        // Check if an ESP32 is already connected.
        if (espClient && espClient.readyState === espClient.OPEN) {
            console.log('An ESP32 is already connected. Terminating the new connection.');
            ws.terminate();
            return;
        }

        console.log('ESP32-CAM client connected.');
        espClient = ws;

        // When the ESP32 sends a message (image or status)
        ws.on('message', (message, isBinary) => {
            // Efficiently forward the message to all connected browser clients.
            browserClients.forEach(client => {
                if (client.readyState === client.OPEN) {
                    client.send(message, { binary: isBinary });
                }
            });

            // For performance tracking
            totalBytes += message.length;
            if (isBinary) {
                frameCount++;
            }
        });

        ws.on('close', () => {
            console.log('ESP32-CAM client disconnected.');
            espClient = null;
        });

        ws.on('error', (err) => {
            console.error('ESP32-CAM client error:', err);
        });

    } else {
        // All other connections are treated as browser clients.
        console.log(`Browser client connected from ${req.socket.remoteAddress}`);
        browserClients.add(ws);

        ws.on('close', () => {
            console.log('Browser client disconnected.');
            browserClients.delete(ws);
        });

        ws.on('error', (err) => {
            console.error('Browser client error:', err);
        });
    }
});

// --- HTTP Server Logic ---

// Serve the main HTML file.
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// --- Server Start ---
httpServer.listen(WEB_PORT, () => {
    console.log(`HTTP and WebSocket server started. Open http://localhost:${WEB_PORT} in your browser.`);
});

// Optional: Log stats to the console periodically.
setInterval(() => {
    if (frameCount > 0) {
        console.log(`Streaming FPS: ${frameCount} | Data Rate: ${(totalBytes / 1024 / 1024).toFixed(2)} MB/s`);
        frameCount = 0;
        totalBytes = 0;
    }
}, 1000);
