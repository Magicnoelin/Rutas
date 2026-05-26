# GUÍA RÁPIDA: Traducciones de Eventos Culturales

## 🎯 Objetivo
Completar las traducciones que faltan en la tabla `cultural_events_trads` para eventos activos.

## 🚀 Cómo generar las traducciones (2 clics)

### 1️⃣ Ir a Eventos
https://rutasrurales.io/admin_tablas/eventos_index.php

### 2️⃣ Darle al botón **"Generar Traducciones"**
- Está arriba a la derecha, al lado de "Regenerar Sitemap i18n"
- Genera automáticamente inglés, francés, alemán y chino para todos los eventos que no tengan esas traducciones
- No sobrescribe las existentes

### 3️⃣ Darle al botón **"Regenerar Sitemap i18n"**
- Para que las URLs traducidas aparezcan en el sitemap

### ✅ ¡Listo!

## 📊 Situación Actual
- **109 eventos** activos en español
- **~53-54 traducciones** por idioma (deberían ser 109)
- **Faltan ~224 traducciones**

## ⚠️ Notas
- La tabla se llama `cultural_events_trads` (sin guión bajo entre "events" y "trads")
- No tiene columnas `created_at` ni `updated_at`
- Idiomas: `en` (inglés), `fr` (francés), `de` (alemán), `zh` (chino)
- El script SQL válido es `completar_traducciones_eventos_ultima_version.sql`
