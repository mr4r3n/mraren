import { useAuth } from "../auth/AuthProvider";

export default function ChatBox() {
    const auth = useAuth();
    
    return <div id="chat" className="h-screen flex flex-col">
                
                <div className="flex-1 overflow-y-auto p-2 pb-20">
                    <ul id="messages" role="list" className="m-0 p-0 space-y-4 flex flex-col">
                        <li className="flex items-start space-x-2 p-2 text-gray-100">
                            <img src="https://pbs.twimg.com/media/Fn5qjz9WQAAXUgE.jpg" alt="User Avatar" className="w-10 h-10 rounded-full"/>
                            <div>
                                <div className="flex items-center space-x-2">
                                <span className="text-sm font-semibold text-gray-100">{auth.getUser()?.username || ""}</span>
                                <span className="text-xs text-gray-400">Thu at 00:53</span>
                                </div>
                                <div className="inline-block bg-[#2f3136] text-gray-100 px-4 py-2 rounded-lg mt-1 max-w-max">
                                Ya falta poco prross
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div className="bg-[#2f3136] p-4 fixed bottom-0 left-0 w-full z-10" >
                    <form className="flex items-center space-x-4" id="form" >

                        <img src="https://via.placeholder.com/40" alt="User Avatar" className="w-10 h-10 rounded-full" />

                        <input type="text" id="input" className="w-full bg-[#2f3136] text-gray-100 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#7289da]" placeholder="Type a message..." required />
                        
                        <button type="submit" className="bg-[#7289da] text-white px-4 py-2 rounded-lg focus:outline-none hover:bg-[#5b6e96]">Send</button>
                    </form>
                </div>
            </div>
}