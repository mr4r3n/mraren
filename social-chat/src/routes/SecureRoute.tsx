import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../auth/AuthProvider";
import { useState, useEffect } from "react";

export default function SecureRoute() {
    const auth = useAuth();
    const [showSpinner, setShowSpinner] = useState(true);

    // Controlar el spinner durante 2 segundos
    useEffect(() => {
        if (auth.loading) {
            const timer = setTimeout(() => {
                setShowSpinner(false);
            }, 2000); // 2 segundos

            return () => clearTimeout(timer);
        } else {
            setShowSpinner(false);
        }
    }, [auth.loading]);

    // Mostrar spinner durante 2 segundos antes de evaluar la autenticación
    if (showSpinner || auth.loading) {
        return (
            <div className="flex justify-center items-center h-screen bg-[#2f3136]">
                <div className="w-16 h-16 border-4 border-gray-300 border-t-transparent rounded-full animate-spin"></div>
            </div>
        );
    }

    // Redirige si no está autenticado
    return auth.isAuth ? <Outlet /> : <Navigate to={"/"} />;
}
