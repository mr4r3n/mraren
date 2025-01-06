const jwt = require("jsonwebtoken");
require("dotenv").config()

function verifyAccessToken(token) {

    return jwt.verify(token, process.env.ACCESS_TOKEN_SECRET);

}

function verifyRefreshToken(token) {

    return jwt.verify(token, process.env.REFRESH_TOKEN_SECRET);

}



module.exports = { verifyAccessToken, verifyRefreshToken }