# Sistema de Traducciones para Eventos Culturales

Este directorio contiene herramientas y scripts para gestionar las traducciones de eventos culturales en el sistema Rutas Rurales.

## 📁 Contenido del Directorio

### Scripts SQL
- **`generar_traducciones_eventos_futuros.sql`** - Script principal para generar traducciones de eventos futuros
- **`completar_traducciones_eventos_final.sql`** - Script original de referencia (en raíz del proyecto)

### Scripts de Análisis (Python)
- **`analizar_eventos_futuros.py`** - Analiza eventos futuros del sitemap que necesitan traducciones
- **`comparar_slugs_exacto.py`** - Compara slugs exactos entre sitemaps español e i18n
- **`analizar_sitemaps_simple.py`** - Análisis simple de correspondencia entre sitemaps
- **`analizar_traducciones_faltantes.py`** - Versión Python del analizador (requiere mysql-connector)

### Scripts de Análisis (PHP)
- **`analizar_traducciones_faltantes.php`** - Versión PHP del analizador (requiere PHP instalado)

## 🎯 Objetivo

Automatizar la generación de traducciones para eventos culturales que:
1. Estén activos (`is_active = 1`)
2. Tengan fecha de inicio futura (`start_date >= CURDATE()`)
3. No tengan traducciones completas en los 4 idiomas (en, fr, de, zh)

## 🔧 Cómo Usar

### 1. Análisis de Traducciones Faltantes
```bash
cd /home/olga/Proyectos/Rutas
python3 traducciones_eventos/analizar_eventos_futuros.py
```

### 2. Generar Traducciones
1. Abrir phpMyAdmin o cliente MySQL
2. Ejecutar el script SQL:
   ```sql
   -- Copiar y pegar el contenido de:
   -- traducciones_eventos/generar_traducciones_eventos_futuros.sql
   ```

### 3. Verificar Resultados
El script SQL incluye secciones de verificación que muestran:
- Traducciones insertadas por idioma
- Eventos con traducciones completas
- Eventos con traducciones incompletas

## 📊 Estructura de Traducciones

### Tabla `cultural_events_trads`
Cada traducción incluye:
- `event_id`: ID del evento original
- `language_code`: Código de idioma (en, fr, de, zh)
- `name`: Nombre del evento (mismo que original)
- `slug`: Slug SEO optimizado para el idioma
- `short_description`: Descripción corta optimizada para SEO
- `description`: Descripción completa con HTML optimizado
- `program`: Programa del evento
- `target_audience`: Público objetivo
- `accessibility`: Información de accesibilidad
- `meta_title`: Título meta optimizado para SEO
- `meta_description`: Descripción meta optimizada para SEO

### Patrones de Slugs SEO
- **Inglés (en)**: `nombre-evento-traditional-festival-provincia-spain-2026`
- **Francés (fr)**: `nombre-evento-fete-traditionnelle-provincia-espagne-2026`
- **Alemán (de)**: `nombre-evento-traditionelles-fest-provincia-spanien-2026`
- **Chino (zh)**: `nombre-evento-chuantongjieri-provincia-xibanya-2026`

## 🚀 Características SEO

### Contenidos "Vitaminados"
Cada traducción incluye:

#### 1. Estructura HTML Optimizada
```html
<h1>Título Principal - Provincia, España</h1>
<h2>Puntos Destacados del Festival</h2>
<ul>
  <li><strong>Música & Danza Tradicional:</strong> ...</li>
  <li><strong>Gastronomía Local:</strong> ...</li>
</ul>
<h2>Información Práctica</h2>
```

#### 2. Palabras Clave por Idioma
- **Inglés**: "traditional festival", "Spanish culture", "international tourists"
- **Francés**: "fête traditionnelle", "culture espagnole", "touristes internationaux"
- **Alemán**: "traditionelles Fest", "spanische Kultur", "internationale Touristen"
- **Chino**: "传统节日", "西班牙文化", "国际游客"

#### 3. Información Práctica
- Fechas exactas con duración
- Ubicación completa (lugar, municipio, provincia)
- Precio de entrada (gratis o con costo)
- Consejos para visitantes

## 📈 Análisis de Sitemaps

### Correspondencia entre Sitemaps
- `sitemap-eventos.xml`: Eventos en español (109 eventos)
- `sitemap-eventos-i18n.xml`: Eventos traducidos (212 slugs únicos)

### Estadísticas Actuales
- 109 eventos en español
- 53 traducciones por idioma (en, fr, de, zh)
- Total: 212 slugs únicos en i18n
- Cada evento debería tener 4 traducciones (436 teóricas)

## 🔄 Flujo de Trabajo Recomendado

1. **Análisis Mensual**
   ```bash
   python3 traducciones_eventos/analizar_eventos_futuros.py
   ```

2. **Generar Traducciones Faltantes**
   - Ejecutar `generar_traducciones_eventos_futuros.sql` en MySQL

3. **Verificación**
   - Revisar las secciones de verificación del script SQL
   - Verificar slugs únicos

4. **Regenerar Sitemap**
   - Ejecutar script de generación de sitemap i18n
   - Verificar que todos los slugs estén incluidos

## ⚠️ Consideraciones Importantes

### 1. Unicidad de Slugs
Los slugs generados deben ser únicos. El script usa:
- Nombre del evento
- Provincia
- Año
- Sufijo específico por idioma

### 2. Eventos Futuros vs Pasados
Solo se traducen eventos con `start_date >= CURDATE()`
- Evita traducir eventos ya pasados
- Optimiza recursos y mantiene relevancia

### 3. Mantenimiento
- Revisar periódicamente eventos sin traducciones completas
- Actualizar patrones de slugs según mejores prácticas SEO
- Ajustar contenidos según feedback de usuarios

## 📋 Archivos Relacionados en el Proyecto

- `api/evento-data.php` - API que consume las traducciones
- `api/evento-detalle.php` - Lógica de detalle de eventos con traducciones
- `actualizar-sitemap.php` - Script para regenerar sitemaps
- `generar_sitemap_eventos_xml.php` - Generador de sitemap de eventos

## 🆘 Solución de Problemas

### "No se encontraron eventos futuros"
- Verificar que `sitemap-eventos.xml` exista y tenga URLs válidas
- Comprobar que los slugs contengan años (ej: `evento-2026`)

### "Error de conexión a MySQL"
- Verificar credenciales en `api/config.php`
- Asegurar que el servidor MySQL esté activo

### "Slugs duplicados"
- Revisar la unicidad en la tabla `cultural_events_trads`
- Ajustar el patrón de generación de slugs si es necesario

## 📞 Soporte

Para problemas o mejoras:
1. Revisar logs de ejecución
2. Verificar estructura de la base de datos
3. Consultar documentación del sistema de eventos

---

*Última actualización: Abril 2026*  
*Sistema: Rutas Rurales - Traducciones de Eventos Culturales*