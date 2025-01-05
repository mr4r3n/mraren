function getHeaderToken(headers) {
    
    if (!headers || !headers.authorization) {
        console.log("Encabezados no válidos o no se pasó autorización:", headers);
        return null;
    }

    const parted = headers.authorization.split(' ');
    if (parted.length === 2) {
        return parted[1];
    } else {
        console.log("Encabezado de autorización mal formado:", headers.authorization);
        return null;
    }
}

module.exports = getHeaderToken;
