const turso = require("../config/tursoClient");
const { v4: uuidv4 } = require("uuid");

async function sendMessage(user, username, message, time) {
    try {
        const id = uuidv4();
        const result = await turso.execute(
            "INSERT INTO chats (id, user_id, username, message, sent_at) VALUES (?, ?, ?, ?, ?)",
            [id, user, username, message, time]
        );
        return result;
    } catch (error) {
        console.error("Error al enviar el mensaje:", error);
        throw new Error("No se pudo enviar el mensaje");
    }
}

async function getMessages(lastMessage) {
    try {
        if (!lastMessage || typeof lastMessage !== "string") {
            throw new Error("ID de mensaje no válido");
        }
        const results = await turso.execute(
            "SELECT id, message FROM chats WHERE id > ?",
            [lastMessage]
        );
        return results;
    } catch (error) {
        console.error("Error al obtener mensajes:", error);
        throw new Error("No se pudieron recuperar los mensajes");
    }
}


module.exports = { sendMessage, getMessages };