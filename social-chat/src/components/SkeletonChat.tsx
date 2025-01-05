export default function SkeletonChat() {
    return (
        <div className="p-4 space-y-4">
            {Array.from({ length: 5 }).map((_, index) => (
                <div key={index} className="flex items-start space-x-4 animate-pulse">
                    {/* Avatar Esqueleto */}
                    <div className="w-10 h-10 bg-gray-700 rounded-full"></div>
                    
                    {/* Contenedor de líneas de mensaje */}
                    <div className="flex-1 w-2 space-y-2 py-1">
                        {/* Línea más corta (nombre del usuario) */}
                        <div className="h-4 bg-gray-700 rounded w-1/3"></div>
                        
                        {/* Línea más larga (mensaje del usuario) */}
                        <div className="h-4 bg-gray-700 rounded w-2/3"></div>
                        
                        {/* Línea adicional más corta (segunda línea opcional) */}
                        <div className="h-4 bg-gray-700 rounded w-1/2"></div>
                    </div>
                </div>
            ))}
        </div>
    );
}
