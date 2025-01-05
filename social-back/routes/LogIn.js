const { getUserInfo } = require("../lib/getUserinfo");
const { jsonResponse } = require("../lib/jsonResponse");
const { findUserByUsername, samePassword, buildAccessToken, buildRefreshToken } = require("../schema/user");

const router = require("express").Router();

router.post("/", async (req, res) => {
    const { username, password } = req.body;
    if (!username || !password) {
        return res.status(400).json(
            jsonResponse(400, {
                error: "Campos obligatorios",
            })
        );
    }

    try {
        // Buscar al usuario en la base de datos
        const user = await findUserByUsername(username);
        if (!user) {
            return res.status(400).json(
                jsonResponse(400, {
                    error: "Usuario o contraseña incorrectos",
                })
            );
        }

        // Comparar contraseñas
        const isPasswordValid = await samePassword(password, user.password);

        if (!isPasswordValid) {
            return res.status(400).json(
                jsonResponse(400, {
                    error: "Usuario o contraseña incorrectos",
                })
            );
        }

        // Generar tokens (usando las funciones definidas en user.js)
        const accessToken = await buildAccessToken(user); // Generar token de acceso
        const refreshToken = await buildRefreshToken(user); // Generar token de refresco

        // Responder con los tokens y la información del usuario
        res.status(200).json(
            jsonResponse(200, {
                user: getUserInfo(user),
                accessToken,
                refreshToken,
            })
        );

    } catch (error) {
        console.error("Error en el login:", error.message);
        res.status(500).json(
            jsonResponse(500, {
                error: "Error interno del servidor",
            })
        );
    }
});

module.exports = router;
