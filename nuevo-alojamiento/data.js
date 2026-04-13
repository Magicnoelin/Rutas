const slug = "la-plaza-vinuesa"

fetch(`/api/get-alojamiento.php?slug=${slug}`)
  .then(res => res.json())
  .then(data => {

    // aquí usas tus módulos
    document.querySelector('#hero').innerHTML = renderHero(data)

    document.querySelector('#info').innerHTML = renderInfo(data)

    requestIdleCallback(() => {
      document.querySelector('#galeria').innerHTML = renderGaleria(data)
    })

  })