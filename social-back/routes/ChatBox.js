const { Socket } = require("socket.io");

const router = require("express").Router();

router.get("/", async (req, res) => {
    const {message, user, username, time} = req.body
    if (!message) {
        return res.status(400).json(
            jsonResponse(400, {
            error: "Campos obligatorios"
            })
        )
    }

    

});

module.exports = router;
