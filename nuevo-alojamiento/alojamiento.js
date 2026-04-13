import { alojamientoData } from './data.js'
import { renderHero } from './modules/hero.js'
import { renderGaleria } from './modules/galeria.js'
import { renderInfo } from './modules/info.js'
import { renderCTA } from './modules/cta.js'
import { renderMapa } from './modules/mapa.js'

// HERO inmediato
document.querySelector('#hero').innerHTML = renderHero(alojamientoData)

// INFO inmediata
document.querySelector('#info').innerHTML = renderInfo(alojamientoData)

// GALERÍA lazy
requestIdleCallback(() => {
  document.querySelector('#galeria').innerHTML = renderGaleria(alojamientoData)
})

// CTA
requestIdleCallback(() => {
  document.querySelector('#cta').innerHTML = renderCTA(alojamientoData)
})

// MAPA solo visible
const observer = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) {
    renderMapa(alojamientoData)
    observer.disconnect()
  }
})

observer.observe(document.querySelector('#mapa'))