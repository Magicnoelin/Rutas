export function renderGaleria(data) {
  return `
    <section>
      ${data.photo1.map(img => `
        <img src="${img}" loading="lazy">
      `).join('')}
    </section>
  `
}