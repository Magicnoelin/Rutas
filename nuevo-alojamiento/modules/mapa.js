export function renderMapa(data) {
  const el = document.querySelector('#mapa')

  el.innerHTML = `
    <button class="btn-secondary">Ver mapa</button>
  `

  el.querySelector('button').addEventListener('click', () => {
    el.innerHTML = `
      <iframe 
        src="https://maps.google.com/maps?q=${data.ubicacion.lat},${data.ubicacion.lng}&z=15&output=embed"
        width="100%" height="300">
      </iframe>
    `
  })
}