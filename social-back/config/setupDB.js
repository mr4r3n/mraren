const turso = require("./tursoClient");
require("dotenv").config();

async function setupDatabase() {
    try {
        console.log("Configurando base de datos...");

        // Crear o verificar tabla de usuarios
        await turso.execute(`
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY NOT NULL,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL
            );
        `);
        console.log("Tabla 'users' verificada o creada correctamente.");

        // Crear o verificar tabla de tokens
        await turso.execute(`
            CREATE TABLE IF NOT EXISTS tokens (
                id TEXT PRIMARY KEY NOT NULL,
                token TEXT NOT NULL
            );
        `);
        console.log("Tabla 'tokens' verificada o creada correctamente.");        

        // Crear índice para optimizar búsquedas de tokens
        await turso.execute(`
            CREATE INDEX IF NOT EXISTS idx_token ON tokens (token);
        `);
        console.log("Índice 'idx_token' creado o verificado correctamente.");
    } catch (error) {
        console.error("Error al configurar la base de datos:", error.message);
    }
}

module.exports = setupDatabase;
