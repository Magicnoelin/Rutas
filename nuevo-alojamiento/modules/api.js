// modules/api.js
export async function fetchAlojamiento(slug) {
    // CAMBIO CLAVE: get_alojamiento.php (como está en tu carpeta api)
    const url = `/api/get_alojamiento.php?slug=${slug}`; 
    
    const res = await fetch(url);

    if (!res.ok) {
        throw new Error(`No se encontró el archivo en: ${url}`);
    }
    return await res.json();
}