export function renderHero(data) {
  return `
    <section>
      <h1>${data.name}</h1>
      <img src="${data.hero}" fetchpriority="high" />
    </section>
  `
}