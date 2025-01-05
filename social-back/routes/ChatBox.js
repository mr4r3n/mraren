const { Server } = require("socket.io");
const { createServer } = require("node:http");

const router = require("express").Router();
    router.get("/", (req, res) => {
        const server = createServer(app)
        const io = new Server(server)
        
        res.send("ChatBox")
    })

module.exports = router;