const bcrypt = require("bcrypt");
const { v4: uuidv4 } = require("uuid");
const { createClient } = require("@libsql/client");
const { generateAccessToken, generateRefreshToken } = require("../auth/generateTokens");
const { getUserInfo } = require("../lib/getUserinfo");
const turso = require("../config/tursoClient");

/**
 * Crea un usuario en la base de datos Turso
 * @param {string} username - Nombre de usuario
 * @param {string} email - Correo electrónico
 * @param {string} password - Contraseña en texto plano
 * @param {string} hashedPassword - Contraseña almacenada (hasheada)
 * @returns {Promise<void>}
 */

/**
 * Crea un usuario en la base de datos Turso
 */
async function createUser(username, email, password) {
    try {
        // Verificar si los campos están completos
        if (!username || !email || !password) {
            throw new Error("Todos los campos son obligatorios");
        }

        // Verificar si el usuario o el email ya existen
        const existingUser = await turso.execute(
            "SELECT username, email FROM users WHERE username = ? OR email = ?",
            [username, email]
        );

        if (existingUser.rows.length > 0) {
            const { username: existingUsername, email: existingEmail } = existingUser.rows[0];
            if (existingUsername === username) {
                throw new Error("El nombre de usuario ya está registrado");
            }
            if (existingEmail === email) {
                throw new Error("El correo electrónico ya está registrado");
            }
        }

        // Generar un ID único
        const id = uuidv4();

        // Hashear la contraseña
        const hashedPassword = await bcrypt.hash(password, 10);

        // Insertar el nuevo usuario en la base de datos
        await turso.execute(
            "INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)",
            [id, username, email, hashedPassword]
        );

        console.log("Usuario creado exitosamente con ID:", id);
    } catch (error) {
        console.error("Error al crear usuario:", {
            username,
            email,
            message: error.message,
        });
        throw new Error(
            error.message || "Error inesperado al intentar crear el usuario."
        );
    }
}

async function samePassword(password, hashedPassword) {
    try {
        return await bcrypt.compare(password, hashedPassword);
    } catch (error) {
        console.error("Error al comparar contraseñas:", error.message);
        throw new Error("Error al verificar la contraseña.");
    }
}

/**
 * Busca un usuario por nombre de usuario
 * @param {string} username - Nombre de usuario
 * @returns {Promise<Object|null>} - Retorna el usuario o null si no existe
 */
async function findUserByUsername(username) {
    try {
        const result = await turso.execute(
            "SELECT id, username, email, password FROM users WHERE username = ?",
            [username]
        );
        return result.rows.length > 0 ? result.rows[0] : null;
    } catch (error) {
        console.error("Error al buscar usuario:", error.message);
        throw new Error("Error al buscar el usuario.");
    }
}

async function buildAccessToken(user) {
    const accessToken = generateAccessToken(getUserInfo(user));
    return accessToken;
}

async function buildRefreshToken(user) {
    const refreshToken = generateRefreshToken(getUserInfo(user))
    const id = uuidv4();
    
    try {

        await turso.execute(
            "INSERT INTO tokens (id, token) VALUES (?, ?)",
            [id, refreshToken]
        )
        return refreshToken;

    } catch (error) {
        console.error("Error al guardar el token de actualización:", error.message);
        throw new Error("No se pudo generar el token de actualización.");
    }
}

module.exports = { createUser, samePassword, findUserByUsername, buildAccessToken, buildRefreshToken };

