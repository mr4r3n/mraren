import { useState } from "react"
import { useAuth } from "../auth/AuthProvider"
import { Navigate, useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import { API_URL } from "../auth/restAPI";
import { AuthResponse, AuthResponseError } from "../types/types";

export default function LogIn() {
    const [username, setUsername] = useState("")
    const [password, setPassword] = useState("")
    const goTo = useNavigate()
    const auth = useAuth();

    async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault()

        try {
            
            const response = await fetch(`${API_URL}/LogIn`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    username,
                    password,
                })
            })

            if (response.ok) {
                const json = (await response.json()) as AuthResponse
                
                if (json.body.accessToken && json.body.refreshToken) {
                    auth.saveUser(json);
                }

                goTo("/messenger")

            } else {
                const json = (await response.json()) as AuthResponseError
                Swal.fire({
                    title: '¡Error!',
                    text: `${json.body.error}`,
                    icon: 'error',
                    confirmButtonText: 'OK'
                })
                return
            }
            
        } catch (error) {
            console.log(error)
        }
    }

    if(auth.isAuth) {
        return <Navigate to={"/messenger"} />
    }
    return <div className="bg-[#1e1e1e] min-h-screen">
                <div className="flex min items-center flex-col justify-center px-6 py-12 lg:px-8">
                    <div className="sm:mx-auto sm:w-full sm:max-w-sm">
                        <img className="mx-auto h-24 w-auto" src="https://cdn-icons-png.flaticon.com/512/4082/4082982.png" alt="Your Company" />
                        <h2 className="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">LOGIN</h2>
                    </div>
                    <div className="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                        <form className="space-y-6" onSubmit={handleSubmit}>    
                            <div>
                                <label className="block text-sm/6 font-medium text-gray-300">Usuario</label>
                                <div className="mt-2">
                                    <input 
                                        type="text" 
                                        name="username" 
                                        id="username" 
                                        autoComplete="username"
                                        value={username}
                                        onChange={(e) => setUsername(e.target.value)}  
                                        className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                    />
                                </div>
                            </div>
                            <div>
                                <div className="flex items-center justify-between">
                                    <label className="block text-sm/6 font-medium text-gray-300">Contraseña</label>
                                    <div className="text-sm">
                                        <a href="#" className="font-semibold text-indigo-400 hover:text-indigo-300">Forgot password?</a>
                                    </div>
                                </div>
                                <div className="mt-2">
                                    <input 
                                        type="password" 
                                        name="password" 
                                        id="password"
                                        autoComplete="password" 
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}  
                                        className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                    />
                                </div>
                            </div>
                            <div>
                                <button type="submit" className="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Login</button>
                            </div>
                        </form>
                        <p className="mt-10 text-center text-sm/6 text-gray-500">¿Todavía no te unes? 
                            <a href="/signup" className="font-semibold text-indigo-600 hover:text-indigo-500">  Registrate</a>
                        </p>
                    </div>
                </div>
            </div>
}