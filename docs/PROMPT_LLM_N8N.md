# 🤖 Prompt LLM — Nodo n8n · Generación de Contenido SEO Multilingüe
## rutasrurales.io · Workflow: Generador Automático de Rutas Temáticas

---

## Configuración del nodo HTTP Request (OpenAI)

| Campo         | Valor                                        |
|---------------|----------------------------------------------|
| **Método**    | `POST`                                       |
| **URL**       | `https://api.openai.com/v1/chat/completions` |
| **Auth**      | Header Auth → `Authorization: Bearer sk-...` |
| **Body type** | JSON                                         |
| **Timeout**   | `60000` ms                                   |

---

## System Prompt (campo "system" del messages array)

```
Eres un experto en turismo rural español y redactor SEO multilingüe para rutasrurales.io.
Generas contenido evocador, específico y optimizado para SEO sobre rutas rurales en España.
Conoces la geografía, gastronomía, historia y cultura de todas las provincias españolas.
REGLA ABSOLUTA: Devuelves ÚNICAMENTE un objeto JSON válido. Sin texto extra. Sin markdown. Sin backticks.
```

---

## User Prompt (campo "user" del messages array — con expresiones n8n `={{ }}`)

```
={{ 
'Genera contenido SEO en Español y Francés para esta Ruta Rural Temática en España.\n\n'
+ 'DATOS DE LA RUTA:\n'
+ 'Provincia: ' + $json.ancla_provincia + '\n'
+ 'Municipio de inicio: ' + $json.ancla_municipio + '\n'
+ 'Distancia total por carretera: ' + $json.distancia_total_km + ' km\n'
+ 'Duración estimada en coche: ' + $json.duracion_horas_min + '\n'
+ 'Número de paradas: ' + $json.total_paradas + '\n\n'
+ 'PARADAS EN ORDEN ÓPTIMO:\n'
+ JSON.stringify($json.paradas_para_llm, null, 2)
+ '\n\nDevuelve ÚNICAMENTE este JSON con todos los campos rellenos:\n'
+ '{\n'
+ '  "titulo_es": "Título atractivo SEO en español, máx 65 chars",\n'
+ '  "titulo_fr": "Titre en français, max 65 chars",\n'
+ '  "slug_sugerido": "slug-en-minusculas-sin-acentos",\n'
+ '  "seo_title_es": "Meta title ES máx 65 chars con provincia y año 2026",\n'
+ '  "seo_title_fr": "Meta title FR max 65 chars",\n'
+ '  "seo_description_es": "Meta description ES máx 155 chars, evocadora, CTA suave",\n'
+ '  "seo_description_fr": "Meta description FR max 155 chars",\n'
+ '  "seo_keywords_es": "keyword1 long-tail, keyword2, keyword3, keyword4, keyword5",\n'
+ '  "seo_keywords_fr": "mot-cle1, mot-cle2, mot-cle3",\n'
+ '  "descripcion_larga_es": "400-600 palabras en español. Párrafos separados por doble salto de línea. Incluye nombres de municipios de las paradas, gastronomía local específica, naturaleza, historia. Tono cálido y evocador. Keywords SEO integradas de forma natural. SIN listas con guiones.",\n'
+ '  "descripcion_larga_fr": "300-400 mots en français. Mêmes critères. Paragraphes séparés.",\n'
+ '  "itinerario_detallado": [{ "dia": 1, "titulo_dia": "Título evocador del día", "paradas": [{ "orden": 1, "nombre": "Nombre exacto", "municipio": "Municipio", "tipo": "place|accommodation|activity|event|stop", "descripcion_editorial": "2-3 frases evocadoras específicas, qué ver sentir comer", "tiempo_recomendado": "2h", "mejor_momento": "Mañana|Tarde|Noche|Todo el día", "consejo_local": "Consejo insider no en guías", "icono": "🏰" }] }],\n'
+ '  "schema_tourist_trip": { "@context": "https://schema.org", "@type": "TouristTrip", "name": "IDÉNTICO a titulo_es", "description": "IDÉNTICO a seo_description_es", "touristType": ["Turismo rural", "Turismo cultural"], "itinerary": [{ "@type": "TouristDestination", "name": "lugar", "description": "desc breve", "geo": { "@type": "GeoCoordinates", "latitude": 0.0, "longitude": 0.0 }, "containedInPlace": { "@type": "AdministrativeArea", "name": "municipio" } }], "provider": { "@type": "Organization", "name": "rutasrurales.io", "url": "https://rutasrurales.io" }, "url": "https://rutasrurales.io/rutas/SLUG", "inLanguage": ["es","fr"], "offers": { "@type": "Offer", "price": "0", "priceCurrency": "EUR" } },\n'
+ '  "tipo_ruta_sugerido": "tematica|gastronomica|provincial|temporal",\n'
+ '  "duracion_dias_sugerida": 2,\n'
+ '  "dificultad_sugerida": "facil|moderada|dificil",\n'
+ '  "season": "primavera|verano|otoño|invierno|todo-el-año",\n'
+ '  "cover_color": "#2F5233"\n'
+ '}'
}}
```

---

## Campos de salida → columnas MySQL `routes`

| Campo JSON LLM           | Columna `routes`                  |
|--------------------------|-----------------------------------|
| `titulo_es`              | `name`                            |
| `titulo_fr`              | `titulo_fr`                       |
| `slug_sugerido`          | `slug` *(nodo Parser añade sufijo)*|
| `seo_title_es`           | `seo_title`                       |
| `seo_title_fr`           | `seo_title_fr`                    |
| `seo_description_es`     | `seo_description`                 |
| `seo_description_fr`     | `seo_description_fr`              |
| `seo_keywords_es`        | `seo_keywords`                    |
| `seo_keywords_fr`        | `seo_keywords_fr`                 |
| `descripcion_larga_es`   | `descripcion_larga_es`            |
| `descripcion_larga_fr`   | `descripcion_larga_fr`            |
| `itinerario_detallado`   | `itinerary_json` *(aplanado)*     |
| `schema_tourist_trip`    | `schema_json`                     |
| `tipo_ruta_sugerido`     | `route_type`                      |
| `duracion_dias_sugerida` | `duration_days`                   |
| `dificultad_sugerida`    | `difficulty_level`                |
| `season`                 | `season`                          |
| `cover_color`            | `cover_color`                     |

---

## Coste estimado por ruta (gpt-4o-mini, sept. 2026)

| Concepto               | Tokens  | Coste      |
|------------------------|---------|------------|
| Input (prompt)         | ~1.200  | ~$0.00018  |
| Output (JSON completo) | ~2.500  | ~$0.00150  |
| **Total por ruta**     | —       | **~$0.002**|
| 52 rutas/año (1/semana)| —       | **~$0.10** |
