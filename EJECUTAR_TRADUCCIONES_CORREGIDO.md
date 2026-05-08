# EJECUTAR SCRIPT SQL CORREGIDO PARA TRADUCCIONES

## ✅ **Error Corregido**

**Problema anterior:** El script SQL intentaba insertar columnas `created_at` y `updated_at` que no existen en la tabla `cultural_events_trads`.

**Solución aplicada:** He corregido el script `actualizar_traducciones_eventos.sql` para que coincida con la estructura real de la tabla, eliminando las columnas `created_at` y `updated_at` de todas las inserciones.

## 📋 **Estado Actual (Análisis del 17/04/2026)**

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

## 🚀 **Cómo Ejecutar el Script Corregido**

### Paso 1: Verificar que MySQL esté instalado
```bash
# Verificar si mysql está disponible
mysql --version

# Si no está instalado, instalar:
sudo apt install mysql-client-core-8.0 -y
```

### Paso 2: Ejecutar el Script SQL Corregido
```bash
# Navegar al directorio del proyecto
cd /home/olga/Proyectos/Rutas



### Paso 3: Verificar Resultados
El script SQL incluye secciones de verificación que mostrarán:

**1. Estado actual antes de la actualización:**
- Total eventos activos
- Eventos con traducciones por idioma
- Eventos con las 4 traducciones completas

**2. Inserción de traducciones faltantes:**
- Para inglés (en)
- Para francés (fr)
- Para alemán (de)
- Para chino (zh)

**3. Estado final después de la actualización:**
- Nuevo total de traducciones
- Eventos completos vs incompletos

**4. Detalle por evento:**
- Lista de todos los eventos
- Estado de traducciones por idioma (✓ o ✗)
- Estado general (COMPLETO o INCOMPLETO)

## 🔧 **Qué hace el Script SQL Corregido**

### 1. **Verificación Inicial**
Muestra cuántos eventos activos hay y cuántas traducciones tienen.

### 2. **Inserción de Traducciones Faltantes**
Para cada idioma (en, fr, de, zh):
- Inserta traducciones para eventos que no las tienen
- Genera slugs SEO optimizados:
  - Inglés: `slug-traditional-festival-spain`
  - Francés: `slug-fete-traditionnelle-espagne`
  - Alemán: `slug-traditionelles-fest-spanien`
  - Chino: `slug-chuantongjieri-xibanya`

### 3. **Contenidos Generados**
Cada traducción incluye:
- **Short description**: Descripción corta optimizada para SEO
- **Description**: Descripción completa con HTML estructurado
- **Program**: Programa del evento
- **Target audience**: Público objetivo específico
- **Accessibility**: Información de accesibilidad
- **Meta title**: Título meta optimizado
- **Meta description**: Descripción meta optimizada

### 4. **Verificación Final**
Muestra el estado después de la actualización:
- Total eventos con traducciones completas
- Eventos que aún necesitan traducciones
- Detalle por evento

## ⚠️ **Consideraciones Importantes**

### 1. **Unicidad de Slugs**
Los slugs generados son únicos porque:
- Usan el slug original del evento
- Agregan sufijo específico por idioma
- Incluyen información de ubicación

### 2. **Eventos Cubiertos**
El script solo actualiza eventos que:
- Están activos (`is_active = 1`)
- Tienen fecha posterior al 1 de abril 2026

### 3. **No Sobrescribe Traducciones Existentes**
El script usa `INSERT ... WHERE NOT EXISTS`, por lo que:
- No modifica traducciones existentes
- Solo inserta traducciones faltantes
- Mantiene los datos existentes intactos

## 🆘 **Solución de Problemas**

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

## 📊 **Resultado Esperado**

Después de ejecutar el script:
- **Todos los 109 eventos activos** tendrán las 4 traducciones completas
- **436 traducciones** en total (vs. ~212 actuales)
- **Slugs SEO optimizados** para cada idioma
- **Contenidos estructurados** con HTML y meta tags
- **Sitemap i18n actualizado** con todas las URLs traducidas

## ⏱️ **Tiempo Estimado**
- Ejecución del script SQL: 2-5 minutos
- Actualización completa del sistema: 10-15 minutos

## 🔄 **Opcional - Regenerar Sitemap**
```bash
# Si PHP está instalado
php generar-sitemap-eventos-i18n.php

# O ejecutar el script de actualización general
php actualizar-sitemap.php
```

---

## 📁 **Archivos Preparados**

### 1. **Script SQL Principal** (`actualizar_traducciones_eventos.sql`)
- Script completo y corregido
- Incluye verificaciones antes y después
- Genera contenidos "vitaminados"

### 2. **Script de Análisis** (`analizar_traducciones_simple.py`)
- Analiza sitemaps para identificar traducciones faltantes
- No requiere conexión a base de datos

### 3. **Documentación Completa** (`INSTRUCCIONES_ACTUALIZAR_TRADUCCIONES.md`)
- Guía paso a paso detallada
- Solución de problemas
- Resultados esperados

---

**Nota:** Las instalaciones de `python3-pip` y `mysql-client-core-8.0` están en proceso. Una vez que terminen, podrás ejecutar el script SQL corregido.

**Última corrección:** 17 de Abril de 2026 - Error de columnas `created_at` y `updated_at` corregido.
