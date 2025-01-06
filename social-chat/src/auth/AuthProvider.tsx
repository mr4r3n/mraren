import { createContext, useContext, useEffect, useState } from "react";
import { AccessTokenResponse, AuthResponse, User } from "../types/types";

interface AuthProviderProps {
    children: React.ReactNode;
}

interface AuthContextType {
    isAuth: boolean;
    loading: boolean;
    getAccessToken: () => string;
    saveUser: (userData: AuthResponse) => void;
    getRefreshToken: () => string | null;
    getUser: () => User | undefined;
}

// Definir el contexto con tipo adecuado
const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: AuthProviderProps) {
    const [isAuth, setIsAuth] = useState(false);
    const [accessToken, setAccessToken] = useState<string>("");
    const [user, setUser] = useState<User | undefined>();
    const [loading, setLoading] = useState(true);
    const apiUrl = import.meta.env.VITE_API_URL;

    useEffect(() => {
        checkAuth();
    }, []);

    async function requestNewAccessToken(refreshToken: string) {
        try {
            const response = await fetch(`${apiUrl}/refreshToken`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${refreshToken}`,
                },
            });

            if (response.ok) {
                const json = (await response.json()) as AccessTokenResponse;
                if (json.error) {
                    throw new Error(json.error);
                }
                return json.body.accessToken;
            } else {
                throw new Error(response.statusText);
            }
        } catch (error) {
            console.error("Error al solicitar un nuevo accessToken:", error);
            return null;
        }
    }

    async function getUserInfo(accessToken: string) {
        try {
            const response = await fetch(`${apiUrl}/User`, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${accessToken}`,
                },
            });

            if (response.ok) {
                const json = await response.json();
                if (json.error) {
                    throw new Error(json.error);
                }
                return json.body;
            } else {
                throw new Error(response.statusText);
            }
        } catch (error) {
            console.error("Error al obtener información del usuario:", error);
            return null;
        }
    }

    async function checkAuth() {
        setLoading(true); // Iniciar carga
        
        const token = getRefreshToken();

        if (accessToken) {
            setIsAuth(true);
        } else if (token) {
            const newAccessToken = await requestNewAccessToken(token);
            if (newAccessToken) {
                const userInfo = await getUserInfo(newAccessToken);
                if (userInfo) {
                    saveSessionInfo(userInfo, newAccessToken, token);
                }
            }
        }
        setLoading(false); // Finalizar carga
    }

    function saveSessionInfo(userInfo: User, accessToken: string, refreshToken: string) {
        setAccessToken(accessToken);
        localStorage.setItem("token", JSON.stringify(refreshToken));
        setIsAuth(true);
        setUser(userInfo);
    }

    function getAccessToken() {
        return accessToken;
    }

    function getRefreshToken(): string | null {
        const tokenData = localStorage.getItem("token");
        return tokenData ? JSON.parse(tokenData) : null;
    }

    function saveUser(userData: AuthResponse) {
        saveSessionInfo(
            userData.body.user,
            userData.body.accessToken,
            userData.body.refreshToken
        );
    }

    function getUser() {
        return user;
    }

    return (
        <AuthContext.Provider
            value={{
                isAuth,
                loading,
                getAccessToken,
                saveUser,
                getRefreshToken,
                getUser,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

// Asegura que el hook se use dentro de AuthProvider
export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error("useAuth debe estar dentro de AuthProvider");
    }
    return context;
};
