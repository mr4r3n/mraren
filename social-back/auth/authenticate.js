const { jsonResponse } = require("../lib/jsonResponse");
const getHeaderToken = require("./getHeaderToken");
const { verifyAccessToken } = require("./verifyToken");

function authenticate(req, res, next) {
    const token = getHeaderToken(req.headers);

    if (token) {

        const decoded = verifyAccessToken(token);
        if (decoded || decoded.user) {
            req.user = decoded;
            next();
        } else {
            res.status(401).json(jsonResponse(401, { message: "No token provided", }))
        }

    } else {

        res.status(401).json(jsonResponse(401, { message: "No token provided", }))

    }
}

module.exports = authenticate;