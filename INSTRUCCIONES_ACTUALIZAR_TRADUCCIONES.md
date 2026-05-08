# INSTRUCCIONES PARA ACTUALIZAR TRADUCCIONES DE EVENTOS CULTURALES

## 📋 Estado Actual (Análisis del 17/04/2026)

### Estadísticas:
- **109 eventos** en español (`sitemap-eventos.xml`)
- **53-54 traducciones** por idioma (deberían ser 109)
- **20 eventos** con las 4 traducciones completas (de 50 analizados)
- **27 eventos** sin ninguna traducción (de 50 analizados)
- **3 eventos** con traducciones parciales

### Traducciones por idioma:
- Inglés (en): 53 traducciones
- Francés (fr): 53 traducciones  
- Alemán (de): 54 traducciones
- Chino (zh): 53 traducciones

**Faltan aproximadamente 224 traducciones** para completar todos los eventos (109 eventos × 4 idiomas = 436 teóricas, actualmente hay ~212).

## 🛠️ Herramientas Preparadas

### 1. Script SQL Principal
**Archivo:** `actualizar_traducciones_eventos.sql`
**Contenido:** Script SQL completo que:
- Verifica el estado actual
- Inserta traducciones faltantes para los 4 idiomas
- Genera verificaciones finales

### 2. Script de Análisis
**Archivo:** `analizar_traducciones_simple.py`
**Función:** Analiza sitemaps para identificar traducciones faltantes

### 3. Scripts Originales
- `traducciones_eventos/generar_traducciones_eventos_futuros.sql` - Versión completa
- `traducciones_eventos/analizar_eventos_futuros.py` - Análisis avanzado

## 🔧 Cómo Ejecutar la Actualización

### Paso 1: Verificar que MySQL esté instalado
```bash
# Verificar si mysql está disponible
mysql --version

# Si no está instalado, instalar:
sudo apt install mysql-client-core-8.0 -y
```

### Paso 2: Ejecutar el Script SQL
Rutas < actualizar_traducciones_eventos.sql


**Contraseña:** `Rutas5Rurales7$`

### Paso 3: Verificar Resultados
El script SQL incluye secciones de verificación que mostrarán:
1. Estado actual antes de la actualización
2. Traducciones insertadas por idioma
3. Estado final después de la actualización
4. Detalle por evento (qué idiomas tiene cada uno)

### Paso 4: Regenerar Sitemap (Opcional)
```bash
# Si PHP está instalado
php generar-sitemap-eventos-i18n.php

# O ejecutar el script de actualización general
php actualizar-sitemap.php
```

## 📊 Qué hace el Script SQL

### 1. Verificación Inicial
Muestra cuántos eventos activos hay y cuántas traducciones tienen.

### 2. Inserción de Traducciones Faltantes
Para cada idioma (en, fr, de, zh):
- Inserta traducciones para eventos que no las tienen
- Genera slugs SEO optimizados:
  - Inglés: `slug-traditional-festival-spain`
  - Francés: `slug-fete-traditionnelle-espagne`
  - Alemán: `slug-traditionelles-fest-spanien`
  - Chino: `slug-chuantongjieri-xibanya`

### 3. Contenidos Generados
Cada traducción incluye:
- **Short description**: Descripción corta optimizada para SEO
- **Description**: Descripción completa con HTML estructurado
- **Program**: Programa del evento
- **Target audience**: Público objetivo específico
- **Accessibility**: Información de accesibilidad
- **Meta title**: Título meta optimizado
- **Meta description**: Descripción meta optimizada

### 4. Verificación Final
Muestra el estado después de la actualización:
- Total eventos con traducciones completas
- Eventos que aún necesitan traducciones
- Detalle por evento

## ⚠️ Consideraciones Importantes

### 1. Unicidad de Slugs
Los slugs generados son únicos porque:
- Usan el slug original del evento
- Agregan sufijo específico por idioma
- Incluyen información de ubicación

### 2. Eventos Cubiertos
El script solo actualiza eventos que:
- Están activos (`is_active = 1`)
- Tienen fecha posterior al 1 de abril 2026

### 3. No Sobrescribe Traducciones Existentes
El script usa `INSERT ... WHERE NOT EXISTS`, por lo que:
- No modifica traducciones existentes
- Solo inserta traducciones faltantes
- Mantiene los datos existentes intactos

## 🆘 Solución de Problemas

### "Error de conexión a MySQL"
```bash
# Verificar que el servidor MySQL esté activo
sudo systemctl status mysql

# Verificar credenciales
# Host: localhost
# Database: u412199647_Rutas
# User: u412199647_olgamarin
# Password: Rutas5Rurales7$
```

### "Acceso denegado"
```bash
# Verificar permisos del usuario
mysql -u root -p -e "GRANT ALL PRIVILEGES ON u412199647_Rutas.* TO 'u412199647_olgamarin'@'localhost'; FLUSH PRIVILEGES;"
```

### "Slugs duplicados"
El script maneja automáticamente la unicidad, pero si hay problemas:
```sql
-- Verificar slugs duplicados
SELECT slug, COUNT(*) as count 
FROM cultural_events_trads 
GROUP BY slug 
HAVING count > 1;
```

## 📞 Para Más Información

### Documentación Relacionada
- `traducciones_eventos/README.md` - Documentación completa del sistema
- `RESUMEN_TRADUCCIONES_EVENTOS.md` - Resumen ejecutivo
- `SOLUCION_TRADUCCIONES_EVENTOS.md` - Solución técnica

### Archivos de Configuración
- `api/config.php` - Configuración de base de datos
- `api/evento-data.php` - API que consume las traducciones

---

## ✅ Resultado Esperado

Después de ejecutar el script:
- **Todos los eventos activos** tendrán las 4 traducciones (en, fr, de, zh)
- **436 traducciones** en total (109 eventos × 4 idiomas)
- **Slugs SEO optimizados** para cada idioma
- **Contenidos "vitaminados"** con estructura HTML y meta tags
- **Sitemap i18n actualizado** con todas las URLs traducidas

**Tiempo estimado:** 2-5 minutos para la ejecución del script SQL.

---

*Última actualización: 17 de Abril de 2026*  
*Sistema: Rutas Rurales - Actualización de Traducciones de Eventos*
