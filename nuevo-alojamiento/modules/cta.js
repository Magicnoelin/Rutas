export function renderCTA(data) {
  return `
    <section>
      <a href="tel:${data.phone}">Llamar</a>
    </section>
  `
}