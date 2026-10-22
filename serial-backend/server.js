const express = require("express");
const { SerialPort, ReadlineParser } = require("serialport");
const http = require("http");
const cors = require("cors");
const { Server } = require("socket.io");
const crypto = require("crypto");
const path = require("path");

// Share Laravel's configuration regardless of the directory we start from.
// Variables supplied by the shell/service retain precedence.
try {
    process.loadEnvFile(path.resolve(__dirname, "../.env"));
} catch (err) {
    if (err.code !== "ENOENT") throw err;
}

// Shared secret the Laravel pages send in X-Serial-Token; refuse to start without it.
const API_TOKEN = process.env.SERIAL_API_TOKEN;
if (!API_TOKEN) {
    console.error("SERIAL_API_TOKEN is not set. Set it in the project root .env or the service environment before starting.");
    process.exit(1);
}
// Comma-separated origins of the Laravel app, e.g. "http://172.0.6.250".
const ALLOWED_ORIGINS = (process.env.SERIAL_ALLOWED_ORIGINS || "")
    .split(",")
    .map((o) => o.trim())
    .filter(Boolean);

function isValidToken(token) {
    if (typeof token !== "string") return false;
    const a = Buffer.from(token);
    const b = Buffer.from(API_TOKEN);
    return a.length === b.length && crypto.timingSafeEqual(a, b);
}

const app = express();
app.use(cors({ origin: ALLOWED_ORIGINS }));
app.use(express.json());
app.use("/api", (req, res, next) => {
    if (isValidToken(req.get("X-Serial-Token"))) return next();
    res.status(401).json({ success: false, error: "Unauthorized" });
});

const server = http.createServer(app);
const io = new Server(server, { cors: { origin: ALLOWED_ORIGINS } });

const commandQueue = [];
let isProcessing = false;
let busIdleTimer = null;
let safetyTimeout = null;
let currentCommand = null;

const BUS_IDLE_DELAY = 3000; // ms of silence before bus is free
const SAFETY_TIMEOUT = 5000; // fallback release
const MAX_RETRIES = 3;
const RETRY_DELAY_MS = 500;

let currentPort = null;
let serialPortInstance = null;

let autoConnectEnabled = true;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 3;
let isConnecting = false;
let manualDisconnect = false; // Track if disconnect was manual

// Default connection settings for Arduino
const DEFAULT_ARDUINO_CONFIG = {
    baudRate: 9600,
    dataBits: 8,
    stopBits: 1,
    parity: "none",
    flowControl: "none",
};

function log(level, message, extra = {}) {
    const timestamp = new Date().toISOString();
    const logEntry = `[${timestamp}] [${level.toUpperCase()}] ${message}`;
    if (Object.keys(extra).length > 0) {
        console[level === "error" ? "error" : "log"](logEntry, extra);
    } else {
        console[level === "error" ? "error" : "log"](logEntry);
    }
}

function broadcastStatus() {
    io.emit("status", {
        connected: !!serialPortInstance && serialPortInstance.isOpen,
        path: currentPort,
        autoConnectEnabled: autoConnectEnabled,
    });
}

function releaseBus() {
    if (busIdleTimer) clearTimeout(busIdleTimer);
    if (safetyTimeout) clearTimeout(safetyTimeout);
    isProcessing = false;
    currentCommand = null;
    processQueue(); // send next queued command
}

function resetIdleTimer() {
    if (busIdleTimer) clearTimeout(busIdleTimer);
    busIdleTimer = setTimeout(() => {
        releaseBus();
    }, BUS_IDLE_DELAY);
}

function processQueue() {
    if (isProcessing || commandQueue.length === 0) return;
    if (!serialPortInstance || !serialPortInstance.isOpen) {
        // Keep commands queued until a connection event (or a later enqueue)
        // calls processQueue again. Polling here would spin while disconnected.
        return;
    }

    const entry = commandQueue.shift();
    currentCommand = entry;
    isProcessing = true;

    serialPortInstance.write(entry.data + "\n", (err) => {
        if (err) {
            releaseBus();
            io.emit("message", {
                type: "error",
                text: `Write error: ${err.message}`,
            });
        } else {
            log("info", "Command sent to controller", { command: entry.data });
            safetyTimeout = setTimeout(() => {
                // Retry logic
                if (entry.retries < MAX_RETRIES) {
                    entry.retries++;
                    log("warn", `Retry ${entry.retries} for: ${entry.data}`);
                    setTimeout(() => {
                        commandQueue.unshift(entry);
                        processQueue();
                    }, RETRY_DELAY_MS);
                    releaseBus();
                } else {
                    io.emit("message", {
                        type: "error",
                        text: `Command failed after ${MAX_RETRIES} attempts: ${entry.data}`,
                    });
                    releaseBus();
                }
            }, SAFETY_TIMEOUT);
        }
    });
}

function enqueueCommand(data, priority = false) {
    const entry = { data, retries: 0, priority };
    if (priority) {
        commandQueue.unshift(entry);
        log("info", "Priority command queued", { command: data });
    } else {
        commandQueue.push(entry);
        log("info", "Command queued", { command: data });
    }
    processQueue();
}

// USB vendor IDs: Arduino LLC, Arduino SRL, WCH (CH340 clones).
// Path/name matching is too loose on a Pi: it also catches the onboard /dev/ttyAMA0 and /dev/ttyS0.
const ARDUINO_VENDOR_IDS = ["2341", "2a03", "1a86"];

function isArduinoPort(port) {
    return ARDUINO_VENDOR_IDS.includes((port.vendorId || "").toLowerCase());
}

async function findAndConnectArduino() {
    if (!autoConnectEnabled) {
        log("debug", "Auto-connect disabled, skipping");
        return false;
    }

    if (serialPortInstance && serialPortInstance.isOpen) {
        log("debug", "Already connected to a port");
        return true;
    }

    if (isConnecting) {
        log("debug", "Already attempting to connect, skipping");
        return false;
    }

    isConnecting = true;

    try {
        const ports = await SerialPort.list();
        log("debug", "Scanning for Arduino", {
            portCount: ports.length,
            availablePorts: ports.map((p) => p.path),
        });

        // PRIORITIZE COM4 and /dev/ttyUSB0 - Connect immediately
        const com4Port = ports.find((p) => p.path === "COM4");
        const ttyUSB0Port = ports.find((p) => p.path === "/dev/ttyUSB0");

        if (com4Port) {
            log("info", "✅ Found COM4, connecting immediately...");
            const success = await connectToPort("COM4", DEFAULT_ARDUINO_CONFIG);
            isConnecting = false;
            return success;
        }

        if (ttyUSB0Port) {
            log("info", "✅ Found /dev/ttyUSB0, connecting immediately...");
            const success = await connectToPort(
                "/dev/ttyUSB0",
                DEFAULT_ARDUINO_CONFIG,
            );
            isConnecting = false;
            return success;
        }

        // Fallback to auto-detection for other Arduinos
        const arduinoPorts = ports.filter((port) => isArduinoPort(port));

        if (arduinoPorts.length === 0) {
            log("debug", "No Arduino ports found");
            isConnecting = false;
            return false;
        }

        const targetPort = arduinoPorts[0].path;
        log("info", "Found Arduino, attempting auto-connect", {
            port: targetPort,
            manufacturer: arduinoPorts[0].manufacturer,
        });

        const success = await connectToPort(targetPort, DEFAULT_ARDUINO_CONFIG);
        isConnecting = false;
        return success;
    } catch (err) {
        log("error", "Auto-connect scan failed", { error: err.message });
        isConnecting = false;
        return false;
    }
}

async function connectToPort(path, config) {
    if (serialPortInstance && serialPortInstance.isOpen) {
        log("warn", "Connect attempted while already open", { path });
        return false;
    }

    const {
        baudRate = 9600,
        dataBits = 8,
        stopBits = 1,
        parity = "none",
        flowControl = "none",
    } = config;

    try {
        let flow = {};
        if (flowControl === "rtscts") {
            flow.rtscts = true;
        } else if (flowControl === "xonxoff") {
            flow.xon = true;
            flow.xoff = true;
        }

        serialPortInstance = new SerialPort({
            path,
            baudRate,
            dataBits,
            stopBits,
            parity,
            ...flow,
            autoOpen: true,
        });

        currentPort = path;
        manualDisconnect = false; // Reset manual disconnect flag on successful connection

        const parser = serialPortInstance.pipe(
            new ReadlineParser({ delimiter: "\r\n" }),
        );

        serialPortInstance.on("open", () => {
            log("info", `✅ Successfully opened port`, {
                path,
                baudRate,
                dataBits,
                stopBits,
                parity,
                flowControl,
            });
            reconnectAttempts = 0;
            isConnecting = false;
            broadcastStatus();
            io.emit("message", {
                type: "success",
                text: `✅ Auto-connected to Arduino on ${path} @ ${baudRate} baud`,
            });

            // Send initial status command after connection
            setTimeout(() => {
                enqueueCommand("<id=1; com=status>");
            }, 1000);
        });

        parser.on("data", (data) => {
            log("debug", "Received data", { data: data.trim() });
            io.emit("data", data.trim());

            if (isProcessing) {
                resetIdleTimer(); // received data → bus is still busy, extend idle timer
            }
        });

        serialPortInstance.on("error", (err) => {
            log("error", "Serial port error occurred", {
                message: err.message,
                code: err.code,
            });

            const isPermanentError =
                err.message.includes("Access denied") ||
                err.message.includes("Device not found") ||
                err.message.includes("No such file");

            if (
                !isPermanentError &&
                reconnectAttempts < MAX_RECONNECT_ATTEMPTS &&
                !manualDisconnect
            ) {
                reconnectAttempts++;
                log(
                    "info",
                    `Will attempt reconnection (attempt ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})`,
                );
                setTimeout(() => {
                    isConnecting = false;
                    findAndConnectArduino();
                }, 5000);
            } else if (isPermanentError) {
                log("error", "Permanent error, stopping auto-reconnect");
                serialPortInstance = null;
                currentPort = null;
                isConnecting = false;
                broadcastStatus();
                io.emit("message", {
                    type: "error",
                    text: `❌ Serial error: ${err.message}`,
                });
            }
        });

        serialPortInstance.on("close", () => {
            log(
                "info",
                "⚠️ Port closed, will reconnect when device is available...",
            );
            serialPortInstance = null;
            currentPort = null;
            isConnecting = false;

            // Re-enable auto-connect if it was disabled and this wasn't a manual disconnect
            if (!manualDisconnect && !autoConnectEnabled) {
                log("info", "Re-enabling auto-connect for device reconnection");
                autoConnectEnabled = true;
            }

            broadcastStatus();

            if (!manualDisconnect) {
                // Only try to reconnect if it wasn't a manual disconnect
                io.emit("message", {
                    type: "warning",
                    text: `⚠️ Arduino disconnected from ${path} - waiting for reconnection...`,
                });
            }
            if (isProcessing) releaseBus();
        });

        return true;
    } catch (err) {
        log("error", "Failed to create/open serial port", {
            path,
            baudRate,
            error: err.message,
        });
        io.emit("message", {
            type: "error",
            text: `❌ Failed to connect to Arduino: ${err.message}`,
        });
        isConnecting = false;
        return false;
    }
}

// Monitor for port changes (plug/unplug) - CONNECT IMMEDIATELY
async function monitorPortChanges() {
    let lastPorts = new Set();

    setInterval(async () => {
        try {
            const ports = await SerialPort.list();
            const currentPortPaths = new Set(ports.map((p) => p.path));

            // Check for COM4 and /dev/ttyUSB0 specifically (highest priority)
            const com4NowAvailable = currentPortPaths.has("COM4");
            const com4WasAvailable = lastPorts.has("COM4");
            const ttyUSB0NowAvailable = currentPortPaths.has("/dev/ttyUSB0");
            const ttyUSB0WasAvailable = lastPorts.has("/dev/ttyUSB0");

            // If COM4 just appeared (plugged in)
            if (com4NowAvailable && !com4WasAvailable) {
                log("info", "🎯 COM4 detected! Connecting immediately...");
                // Re-enable auto-connect if it was disabled
                if (!autoConnectEnabled) {
                    log("info", "Re-enabling auto-connect for COM4 detection");
                    autoConnectEnabled = true;
                    manualDisconnect = false;
                }
                // Small delay to ensure port is ready
                setTimeout(() => {
                    findAndConnectArduino();
                }, 500);
            }

            // If /dev/ttyUSB0 just appeared (plugged in)
            if (ttyUSB0NowAvailable && !ttyUSB0WasAvailable) {
                log(
                    "info",
                    "🎯 /dev/ttyUSB0 detected! Connecting immediately...",
                );
                // Re-enable auto-connect if it was disabled
                if (!autoConnectEnabled) {
                    log(
                        "info",
                        "Re-enabling auto-connect for /dev/ttyUSB0 detection",
                    );
                    autoConnectEnabled = true;
                    manualDisconnect = false;
                }
                // Small delay to ensure port is ready
                setTimeout(() => {
                    findAndConnectArduino();
                }, 500);
            }

            // Check for new ports added (general case)
            for (const portPath of currentPortPaths) {
                if (!lastPorts.has(portPath)) {
                    log("info", "New port detected", { port: portPath });
                    const newPort = ports.find((p) => p.path === portPath);

                    if (portPath === "COM4" || isArduinoPort(newPort)) {
                        log(
                            "info",
                            "🎯 Arduino detected, connecting immediately",
                            { port: portPath },
                        );
                        // Re-enable auto-connect if it was disabled
                        if (!autoConnectEnabled) {
                            log(
                                "info",
                                "Re-enabling auto-connect for new Arduino detection",
                            );
                            autoConnectEnabled = true;
                            manualDisconnect = false;
                        }
                        // Small delay to ensure port is ready
                        setTimeout(() => {
                            findAndConnectArduino();
                        }, 500);
                    }
                }
            }

            // Check for removed ports
            for (const portPath of lastPorts) {
                if (
                    !currentPortPaths.has(portPath) &&
                    currentPort === portPath
                ) {
                    log("info", "Arduino disconnected", { port: portPath });
                    io.emit("message", {
                        type: "warning",
                        text: `⚠️ Arduino disconnected from ${portPath}`,
                    });
                }
            }

            lastPorts = currentPortPaths;
        } catch (err) {
            log("error", "Port monitoring failed", { error: err.message });
        }
    }, 1000); // Check every 1 second
}

// Force immediate connection to COM4 endpoint (for testing)
app.post("/api/force-connect-com4", async (req, res) => {
    log("info", "Force connecting to COM4");
    manualDisconnect = false;
    autoConnectEnabled = true;
    const success = await connectToPort("COM4", DEFAULT_ARDUINO_CONFIG);
    if (success) {
        res.json({ success: true, message: "Connected to COM4" });
    } else {
        res.status(500).json({
            success: false,
            error: "Failed to connect to COM4",
        });
    }
});

// Force immediate connection to /dev/ttyUSB0 endpoint
app.post("/api/force-connect-ttyusb0", async (req, res) => {
    log("info", "Force connecting to /dev/ttyUSB0");
    manualDisconnect = false;
    autoConnectEnabled = true;
    const success = await connectToPort("/dev/ttyUSB0", DEFAULT_ARDUINO_CONFIG);
    if (success) {
        res.json({ success: true, message: "Connected to /dev/ttyUSB0" });
    } else {
        res.status(500).json({
            success: false,
            error: "Failed to connect to /dev/ttyUSB0",
        });
    }
});

// API endpoint to enable/disable auto-connect
app.post("/api/auto-connect", (req, res) => {
    const { enabled } = req.body;
    autoConnectEnabled = enabled;
    if (!enabled) {
        manualDisconnect = true;
    } else {
        manualDisconnect = false;
    }
    log("info", `Auto-connect ${enabled ? "enabled" : "disabled"}`);

    if (enabled && (!serialPortInstance || !serialPortInstance.isOpen)) {
        findAndConnectArduino();
    }

    res.json({ success: true, autoConnectEnabled });
});

// Get auto-connect status
app.get("/api/auto-connect", (req, res) => {
    res.json({ success: true, autoConnectEnabled });
});

app.get("/api/ports", async (req, res) => {
    try {
        const ports = await SerialPort.list();
        const portList = ports.map((p) => ({
            path: p.path,
            manufacturer: p.manufacturer || "Unknown",
            friendlyName: `${p.path} ${p.manufacturer ? `(${p.manufacturer})` : ""}`,
            isArduino: isArduinoPort(p),
            vendorId: p.vendorId,
            productId: p.productId,
        }));
        res.json({ success: true, ports: portList });
    } catch (err) {
        log("error", "Failed to list ports", {
            error: err.message,
            stack: err.stack,
        });
        res.status(500).json({ success: false, error: err.message });
    }
});

app.post("/api/connect", async (req, res) => {
    const { path, ...config } = req.body;

    if (serialPortInstance && serialPortInstance.isOpen) {
        return res
            .status(400)
            .json({ success: false, error: "Port already open" });
    }

    autoConnectEnabled = false; // Disable auto-connect when manually connecting
    manualDisconnect = true;
    const success = await connectToPort(path, config);

    if (success) {
        res.json({ success: true });
    } else {
        res.status(500).json({ success: false, error: "Failed to connect" });
    }
});

app.post("/api/disconnect", (req, res) => {
    if (!serialPortInstance || !serialPortInstance.isOpen) {
        log("info", "Disconnect called but no port open");
        return res.json({ success: true, message: "No port open" });
    }

    manualDisconnect = true; // Mark as manual disconnect
    autoConnectEnabled = false; // Disable auto-connect on manual disconnect

    serialPortInstance.close((err) => {
        if (err) {
            log("error", "Failed to close port", { error: err.message });
            res.status(500).json({ success: false, error: err.message });
        } else {
            log("info", "Port disconnected successfully (manual)");
            serialPortInstance = null;
            currentPort = null;
            broadcastStatus();
            res.json({ success: true });
        }
    });
});

app.post("/api/send", (req, res) => {
    const { data, priority = false } = req.body;
    if (typeof data !== "string" || !data)
        return res.status(400).json({ success: false, error: "Missing data" });
    // A line break would let one request inject extra commands onto the bus.
    if (/[\r\n]/.test(data))
        return res.status(400).json({ success: false, error: "Invalid data" });
    if (!serialPortInstance || !serialPortInstance.isOpen) {
        return res.status(400).json({ success: false, error: "Not connected" });
    }
    enqueueCommand(data, priority);
    res.json({ success: true, queued: true, priority });
});

// Add endpoint to reset auto-connect
app.post("/api/reset-auto-connect", (req, res) => {
    autoConnectEnabled = true;
    manualDisconnect = false;
    log("info", "Auto-connect reset to enabled");

    if (!serialPortInstance || !serialPortInstance.isOpen) {
        findAndConnectArduino();
    }

    res.json({ success: true, autoConnectEnabled: true });
});

io.on("connection", (socket) => {
    log("info", "New browser client connected");
    broadcastStatus();

    socket.emit("connection-status", {
        connected: !!serialPortInstance && serialPortInstance.isOpen,
        port: currentPort,
        autoConnectEnabled: autoConnectEnabled,
    });
});

process.on("SIGINT", () => {
    if (serialPortInstance && serialPortInstance.isOpen) {
        serialPortInstance.close(() => {
            log("info", "Serial port closed on process exit");
            process.exit(0);
        });
    } else {
        process.exit(0);
    }
});

const PORT = 3123;
server.listen(PORT, "0.0.0.0", async () => {
    log("info", `Serial server + Socket.io running on http://0.0.0.0:${PORT}`);
    log(
        "info",
        "🎯 Auto-connect enabled - Will connect to COM4 immediately when detected",
    );
    log("info", "Monitoring for Arduino on COM4...");

    // Start monitoring for port changes
    monitorPortChanges();

    // Initial attempt to connect to Arduino
    setTimeout(async () => {
        await findAndConnectArduino();
    }, 1000);
});
