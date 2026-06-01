# 🔧 Corrección de errores 404 en URLs traducidas

## Problema
Google Search Console reporta cientos de errores 404 porque el sistema generaba URLs con prefijos de idioma (`/en/alojamiento/slug`, `/fr/alojamiento/slug`, etc.) para **todos** los alojamientos, incluyendo los gratuitos que no tienen contenido traducido.

## Solución aplicada
Se añadió el campo `is_premium` en la tabla `accommodations` para discriminar qué alojamientos tienen derecho a traducciones multiidioma.

---

## Archivos modificados

### 1. `agregar_is_premium.sql` (NUEVO — ejecutar una sola vez)
```sql
ALTER TABLE accommodations 
ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0 
COMMENT '1 = Premium (con derecho a traducciones multiidioma), 0 = Gratuito (solo español)';

-- Actualizar alojamientos cuyos propietarios son premium
UPDATE accommodations a
JOIN user_resources ur ON ur.resource_id = a.id AND ur.resource_type = 'accommodation' AND ur.role = 'owner'
JOIN users u ON u.id = ur.user_id
SET a.is_premium = 1
WHERE u.membership_type IN ('premium', 'enterprise');
```

### 2. `alojamiento-modular/index.php` — 3 cambios

#### A) Redirección 301 (línea 37-42)
Cuando Google o un usuario accede a una URL traducida de un alojamiento gratuito:
```php
if ($alojamiento && empty($alojamiento['is_premium']) && $lang !== 'es') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://rutasrurales.io/alojamiento/' . rawurlencode($alojamiento['slug']));
    exit();
}
```
- `/en/alojamiento/casa-gratuita` → **301** → `/alojamiento/casa-gratuita` (200 OK)
- Search Console deja de reportar 404, consolida el PageRank en español

#### B) hreflang condicional (línea ~580)
Las etiquetas `<link rel="alternate" hreflang="..." />` solo se renderizan si el alojamiento es Premium:
```php
<?php if ($alojamiento && !empty($alojamiento['is_premium'])): ?>
    <!-- Aquí van los 6 <link rel="alternate" hreflang="..." /> -->
<?php endif; ?>
```
Los alojamientos gratuitos NO tienen hreflang → Google entiende que solo existen en español.

#### C) Selector de idiomas (UI)
Donde tengas el desplegable de idiomas en la vista, añade este condicional:
```php
<?php if ($alojamiento && !empty($alojamiento['is_premium'])): ?>
    <!-- SELECTOR DE IDIOMAS VISIBLE con enlaces a /en/, /fr/, /de/, /zh/ -->
<?php else: ?>
    <!-- SIN SELECTOR DE IDIOMAS (alojamiento gratuito) -->
<?php endif; ?>
```

### 3. `generar-sitemap.php` — Filtro en sitemap XML

- Se añadió `xmlns:xhtml="http://www.w3.org/1999/xhtml"` al `<urlset>`
- La consulta SQL ahora incluye `is_premium` en el SELECT
- Solo los alojamientos Premium generan nodos `<xhtml:link rel="alternate" hreflang="..." />` para los 5 idiomas
- Los alojamientos gratuitos solo exportan su URL en español

---

## Cómo validará Google la corrección

1. **Antes:** Googlebot encuentra `/en/alojamiento/casa-gratuita` → **404** → error en Search Console
2. **Después:** Googlebot vuelve a rastrear esa URL → recibe **301 Moved Permanently** a `/alojamiento/casa-gratuita` → 200 OK
3. Search Console marcará el error como **"Corregido"** (un 301 es una respuesta válida)
4. El sitemap.xml ya no contiene URLs traducidas de gratuitos → Google no las descubre nuevas
5. **Tiempo estimado:** 1 a 4 semanas para que Search Console refleje todos los cambios

---

## Mantenimiento futuro

Cuando un usuario haga **upgrade a Premium**, debes ejecutar:
```sql
UPDATE accommodations SET is_premium = 1 WHERE id IN (
    SELECT resource_id FROM user_resources 
    WHERE resource_type = 'accommodation' AND role = 'owner' 
    AND user_id = (SELECT id FROM users WHERE email = 'email_del_usuario')
);
```

Cuando un usuario **cancele su membresía Premium**, ejecuta:
```sql
UPDATE accommodations SET is_premium = 0 WHERE id IN (
    SELECT resource_id FROM user_resources 
    WHERE resource_type = 'accommodation' AND role = 'owner' 
    AND user_id = (SELECT id FROM users WHERE email = 'email_del_usuario')
);
```
