const express = require("express");
const cors = require("cors");
const { createClient } = require("@libsql/client");
const setupDatabase = require("./config/setupDB");
const authenticate = require("./auth/authenticate");
const { Server } = require("socket.io");
const { createServer } = require("node:http");
const { verifyRefreshToken } = require("./auth/verifyToken");
const { v4: uuidv4 } = require("uuid");

const app = express();
require("dotenv").config();

const port = process.env.PORT || 3000;
const server = createServer(app);

// Conexión a la base de datos
const turso = createClient({
    url: process.env.TURSO_DATABASE_URL,
    authToken: process.env.TURSO_AUTH_TOKEN,
});

setupDatabase().then(() => {
    console.log("Base de datos configurada correctamente.");
}).catch((error) => {
    console.error("Error al configurar la base de datos:", error.message);
});

// Middleware y rutas
app.use(cors({
    origin: ["http://localhost:5173"],
    methods: ["GET", "POST"]
}));
app.use(express.json());

app.use('/api/LogIn', require('./routes/LogIn'));
app.use('/api/SignUp', require('./routes/SignUp'));
app.use('/api/SignOut', require('./routes/SignOut'));
app.use('/api/refreshToken', require('./routes/refreshToken'));
app.use('/api/ChatBox', authenticate, require('./routes/ChatBox'));
app.use('/api/User', authenticate, require('./routes/User'));

const io = new Server(server, {
    cors: {
        origin: ["http://localhost:5173"],
        methods: ["GET", "POST"]
    },
    connectionStateRecovery: {
        maxDisconnectionDuration: 5,
    }
});

// Middleware de autenticación de WebSockets
io.use(async (socket, next) => {
    const token = socket.handshake.auth.token;
    const user_id = socket.handshake.auth.user_id;  // Obtener user_id del handshake
    const username = socket.handshake.auth.username;

    if (!token || !user_id || !username) {
        return next(new Error("No autorizado - Falta token o datos de usuario."));
    }

    try {
        const user = await verifyRefreshToken(token);  // Validar el token
        socket.user = {
            id: user_id,
            username: username
        };
        next();
    } catch (error) {
        console.log("Error de autenticación:", error);
        next(new Error("Token inválido"));
    }
});


// Manejo de conexiones y mensajes
io.on('connection', async (socket) => {
    console.log(`Usuario conectado: ${socket.user.username}`);

    socket.on('disconnect', (reason) => {
        if (reason === 'transport close') {
            console.log(`Usuario desconectado: ${socket.user.username}`);
        }
    });

    socket.on('chat message', async (message) => {
        try {
            const message_id = uuidv4();
            const time = new Date().toISOString();
            user_id = socket.handshake.auth.user_id
            username = socket.handshake.auth.username

            const result = await turso.execute(
                "INSERT INTO chats (id, user_id, username, message, sent_at) VALUES (?, ?, ?, ?, ?)",
                [message_id, user_id, username, message, time]
            );
            // Obtener el incremental_id recién generado
            const offsetResult = await turso.execute(
                "INSERT INTO chat_offsets (message_id) VALUES (?) RETURNING id",
            [message_id]
            );
            const serverOffset = offsetResult.rows[0].id;

            // Emitir el mensaje a todos los clientes junto con el incremental_id
            io.emit('chat message', message, serverOffset, time, username);

        } catch (error) {
            console.error("Error al guardar el mensaje:", error);
        }
    });

    if (!socket.recovered) {
        try {
            const offset = parseInt(socket.handshake.auth.serverOffset ?? "0", 10);
    
            // Obtener mensajes donde el incremental_id sea mayor al serverOffset
            const result = await turso.execute(
                `SELECT c.message, o.id, c.sent_at, c.username
                 FROM chat_offsets o
                 JOIN chats c ON o.message_id = c.id
                 WHERE o.id > ?
                 ORDER BY o.id ASC`,
                [offset]
            );
    
            result.rows.forEach(row => {
                socket.emit('chat message', row.message, row.id, row.sent_at, row.username);
            });
    
        } catch (error) {
            console.error("Error al recuperar mensajes:", error);
        }
    }
    
});

app.get("/", (req, res) => {
    res.send("REST API");
});

server.listen(port, () => {
    console.log(`Server running on port: ${port}`);
});
