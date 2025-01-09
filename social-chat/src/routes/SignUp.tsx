import { useState } from "react"
import { useAuth } from "../auth/AuthProvider"
import { Navigate, useNavigate } from "react-router-dom";
import { AuthResponseError } from "../types/types";
import Swal from 'sweetalert2'

export default function SignUp() {
    const [username, setUsername] = useState("")
    const [email, setEmail] = useState("")
    const [password, setPassword] = useState("")
    const goTo = useNavigate()
    const auth = useAuth();
    const apiUrl = import.meta.env.VITE_API_URL;

    async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault()

        try {
            
            const response = await fetch(`${apiUrl}/api/SignUp`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    username,
                    email,
                    password
                })
            })

            if (response.ok) {
                Swal.fire({
                    title: 'Usuario creado',
                    text: 'Bienvenido a la fiesta',
                    icon: 'success',
                    confirmButtonText: 'OK'
                })
                goTo("/")
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
                        <h2 className="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">REGISTRO</h2>
                    </div>
                    <div className="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                        <form className="space-y-6" onSubmit={handleSubmit}>
                            <div>
                                <label className="block text-sm/6 font-medium text-gray-300">Username</label>
                                
                                <div className="mt-2">
                                <input 
                                    type="text" 
                                    name="username" 
                                    id="username" 
                                    value={username}
                                    onChange={(e) => setUsername(e.target.value)}  
                                    className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm/6 font-medium text-gray-300">Email</label>
                                <div className="mt-2">
                                <input 
                                    type="email" 
                                    name="email" 
                                    id="email" 
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}  
                                    className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                </div>
                            </div>
                            <div>
                                <div className="flex items-center justify-between">
                                <label className="block text-sm/6 font-medium text-gray-300">Password</label>
                                </div>
                                <div className="mt-2">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}  
                                    className="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                                />
                                </div>
                            </div>
                            <div>
                                <button type="submit" className="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Registrarse</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

}