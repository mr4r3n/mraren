const { createClient } = require("@libsql/client");
require("dotenv").config(); // Asegúrate de que las variables de entorno están configuradas

// Crear la conexión a la base de datos Turso
const turso = createClient({
    url: process.env.TURSO_DATABASE_URL,    // URL de la base de datos desde tus variables de entorno
    authToken: process.env.TURSO_AUTH_TOKEN // Token de autenticación desde tus variables de entorno
});

// Exportar la instancia para su uso en otros archivos
module.exports = turso;
