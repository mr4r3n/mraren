const router = require("express").Router();
    router.get("/", (req, res) => {
        res.send("SignOut")
    })

module.exports = router