const { generateAccessToken } = require("../auth/generateTokens");
const getHeaderToken = require("../auth/getHeaderToken");
const { verifyRefreshToken } = require("../auth/verifyToken");
const { jsonResponse } = require("../lib/jsonResponse");
const turso = require("../config/tursoClient"); // Conexión a la base de datos Turso

const router = require("express").Router();

router.post("/", async (req, res) => {

    const refreshToken = getHeaderToken(req.headers);

    if (!refreshToken) {
        return res.status(401).json({ error: "Token no proporcionado o inválido" });
    }

    try {
        // Buscar el token en la base de datos
        const query = `
            SELECT token 
            FROM tokens 
            WHERE token = ?;
        `;
        const result = await turso.execute(query, [refreshToken]);

        if (result.rows.length === 0) {
            return res.status(401).json(jsonResponse(401, { error: "Unauthorized" }));
        }

        // Validar el token de refresco
        const payload = verifyRefreshToken(result.rows[0].token);
        
        if (!payload) {
            return res.status(401).json(jsonResponse(401, { error: "Unauthorized" }));
        }

        // Generar un nuevo access token
        const accessToken = generateAccessToken(payload);

        return res.status(200).json(jsonResponse(200, { accessToken }));

    } catch (error) {
        console.error("Error while refreshing token:", error);
        return res.status(500).json(jsonResponse(500, { error: "Internal Server Error" }));
    }
});

module.exports = router;
