const express = require("express")
const cors = require("cors")
const { createClient } = require("@libsql/client")
const setupDatabase = require("./config/setupDB");
const authenticate = require("./auth/authenticate");
const app = express()

require("dotenv").config()

const port = process.env.PORT || 3000

const turso = createClient({
    url: process.env.TURSO_DATABASE_URL,
    authToken: process.env.TURSO_AUTH_TOKEN,
});

setupDatabase().then(() => {
    console.log("Base de datos configurada correctamente.");
}).catch((error) => {
    console.error("Error al configurar la base de datos:", error.message);
});

app.use(cors());
app.use(express.json());

app.use('/api/LogIn', require('./routes/LogIn'))
app.use('/api/SignUp', require('./routes/SignUp'))
app.use('/api/SignOut', require('./routes/SignOut'))
app.use('/api/refreshToken', require('./routes/refreshToken'))
app.use('/api/ChatBox', authenticate, require('./routes/ChatBox'))
app.use('/api/User', authenticate, require('./routes/User'))


app.get("/", (req, res) => {
    res.send("REST API")
})

app.listen(port, () => {
    console.log(`Server running on port: ${port}`)
})