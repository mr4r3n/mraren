const jwt = require("jsonwebtoken");

// Función para generar el token
function sign(payload, isAccessToken) {
    return jwt.sign(
        payload,
        isAccessToken
            ? process.env.ACCESS_TOKEN_SECRET
            : process.env.REFRESH_TOKEN_SECRET,
        {
            algorithm: "HS256",
            expiresIn: isAccessToken ? '15m' : '7d', // Cambiar expiración según el tipo de token
        }
    );
}

// Generación de Access Token
function generateAccessToken(user) {
    return sign({ id: user.id, username: user.username }, true); // Solo pasamos lo necesario
}

// Generación de Refresh Token
function generateRefreshToken(user) {
    return sign({ id: user.id, username: user.username }, false); // Solo pasamos lo necesario
}

module.exports = { generateAccessToken, generateRefreshToken };
