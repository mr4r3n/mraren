import { useState, useEffect, useRef } from "react";
import { useAuth } from "../auth/AuthProvider";
import { io, Socket } from "socket.io-client";
import { formatMessageDate } from "../utils/dateUtils";

export default function ChatBox() {
    const auth = useAuth();
    const [messages, setMessages] = useState<{ text: string; user: string; time: string }[]>([]);
    const [input, setInput] = useState("");
    const chatboxRef = useRef<HTMLUListElement | null>(null);
    const socketRef = useRef<Socket | null>(null);  // Almacena la instancia de socket
    const apiUrl = import.meta.env.VITE_API_URL;

    // Establecer conexión de socket solo una vez
    useEffect(() => {
        if (!socketRef.current) {
            // Crear la conexión solo si no existe
            
            socketRef.current = io(`${apiUrl}/ChatBox`, {
                auth: {
                    token: auth.getRefreshToken(),
                    serverOffset: 0,
                    user_id: auth.getUser()?.id,
                    username: auth.getUser()?.username
                }
            });

            // Escuchar mensajes entrantes
            socketRef.current.on('chat message', (msg, serverOffset, sentAt, username) => {
                const newMessage: { text: string; user: string; time: string } = {
                    text: msg,
                    user: username || "Anonymous",
                    time: formatMessageDate(sentAt),  // Formatear la fecha con la nueva función
                };
            
                setMessages((prev) => [...prev, newMessage]);
            
                socketRef.current!.auth.serverOffset = serverOffset;
            
                // Hacer scroll automáticamente
                if (chatboxRef.current) {
                    chatboxRef.current.scrollTop = chatboxRef.current.scrollHeight;
                }
            });
            

            // Limpiar socket al desmontar el componente
            return () => {
                socketRef.current?.disconnect();
                socketRef.current = null;
            };
        }
    }, []);

    // Manejar envío de mensajes
    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (input.trim()) {
            socketRef.current?.emit('chat message', input);
            setInput("");
        }
    };

    return (
        <div id="chat" className="h-screen flex flex-col">
            <div className="flex-1 overflow-y-auto p-2 pb-20">
                <ul
                    ref={chatboxRef}
                    id="chatbox"
                    role="list"
                    className="m-0 p-0 space-y-4 flex flex-col"
                >
                    {messages.map((msg, index) => (
                        <li key={index} className="flex items-start space-x-2 p-2 text-gray-100">
                            <img
                                src="https://pbs.twimg.com/media/Fn5qjz9WQAAXUgE.jpg"
                                alt="User Avatar"
                                className="w-10 h-10 rounded-full"
                            />
                            <div>
                                <div className="flex items-center space-x-2">
                                    <span className="text-sm font-semibold text-gray-100">
                                        {msg.user}
                                    </span>
                                    <span className="text-xs text-gray-400">
                                        {msg.time}
                                    </span>
                                </div>
                                <div className="inline-block bg-[#2f3136] text-gray-100 px-4 py-2 rounded-lg mt-1 max-w-max">
                                    {msg.text}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="bg-[#2f3136] p-4 fixed bottom-0 left-0 w-full z-10">
                <form className="flex items-center space-x-4" onSubmit={handleSubmit}>
                    <img
                        src="https://via.placeholder.com/40"
                        alt="User Avatar"
                        className="w-10 h-10 rounded-full"
                    />

                    <input
                        type="text"
                        name="input"
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder="Type a message..."
                        className="w-full bg-[#2f3136] text-gray-100 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7289da]"
                    />

                    <button
                        type="submit"
                        className="bg-[#7289da] text-white px-4 py-2 rounded-lg focus:outline-none hover:bg-[#5b6e96]"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>
    );
}
