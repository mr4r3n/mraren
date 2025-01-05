const { jsonResponse } = require("../lib/jsonResponse");
const { createUser } = require("../schema/user");

const router = require("express").Router();
    router.post("/", async (req, res) => {
        const {username, email, password} = req.body
        if (!!!username || !!!email || !!!password) {
            return res.status(400).json(
                jsonResponse(400, {
                error: "Campos obligatorios"
                })
            )
        }

        try {
            // Crear el usuario
            await createUser(username, email, password);
            res.status(200).json(
                jsonResponse(200, { message: "Registrado correctamente" })
            );
        } catch (error) {
            res.status(400).json(
                jsonResponse(400, { error: error.message })
            );
        }
    })

module.exports = router