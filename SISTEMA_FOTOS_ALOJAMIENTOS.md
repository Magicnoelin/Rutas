# Sistema de Gestión de Fotos para Alojamientos

## 📸 Descripción General

Este sistema permite organizar y gestionar las fotos de los alojamientos de manera estructurada y optimizada para SEO. Las fotos se organizan por categorías y se convierten automáticamente al formato WebP para mejorar el rendimiento y la experiencia del usuario.

## 🎯 Características Principales

### 1. **Organización por Categorías**
- **Categorías disponibles**: Salón, Cocina, Jardín, Habitación, Cuarto de baño, Exterior, Piscina, Comedor, Terraza, Otro
- **Estructura de carpetas**: `accommodations_images/{slug-del-alojamiento}/`
- **Nombres de archivos**: `{categoria}-{timestamp}.webp`

### 2. **Conversión Automática a WebP**
- Todas las imágenes se convierten automáticamente al formato WebP con calidad del 80%
- Soporte para JPG, PNG y WEBP de entrada
- Preservación de transparencia para imágenes PNG
- Reducción significativa del tamaño de archivo sin pérdida notable de calidad

### 3. **Base de Datos Organizada**
- Tabla `photo_categories` para almacenar metadatos de las fotos
- Relación con la tabla `accommodations` mediante `accommodation_id`
- Información de categoría, URL y fecha de creación

### 4. **Interfaz de Usuario Intuitiva**
- Selección visual de categorías con iconos
- Subida de archivos con arrastrar y soltar
- Galería de fotos organizada por categorías
- Visualización de miniaturas con información

## 📁 Estructura de Archivos

```
accommodations_images/
├── alojamiento-el-mirador-del-cid/
│   ├── salon-1706367695.webp
│   ├── cocina-1706367720.webp
│   ├── jardin-1706367745.webp
│   └── habitacion-1706367770.webp
└── casa-rural-la-encina/
    ├── exterior-1706367800.webp
    ├── piscina-1706367825.webp
    └── bano-1706367850.webp
```

## 🔧 Requisitos del Sistema

### Servidor
- PHP 7.4 o superior
- Extensión GD de PHP habilitada (para conversión WebP)
- Base de datos MySQL/MariaDB
- Permisos de escritura en el directorio `accommodations_images/`

### Cliente
- Navegador moderno (Chrome, Firefox, Safari, Edge)
- JavaScript habilitado

## 🚀 Instalación y Configuración

### 1. Crear la tabla de categorías de fotos
El sistema crea automáticamente la tabla `photo_categories` si no existe:

```sql
CREATE TABLE photo_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accommodation_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### 2. Configurar permisos
Asegúrate de que el directorio `accommodations_images/` tenga permisos de escritura:

```bash
chmod -R 755 accommodations_images/
```

### 3. Verificar extensión GD
Comprueba que la extensión GD esté instalada en tu servidor PHP:

```php
<?php
phpinfo();
?>
```

Busca la sección "gd" para confirmar que está habilitada.

## 📱 Uso del Sistema

### 1. Acceder a la interfaz
- Navega a `gestion-fotos-alojamiento.html`
- Busca el alojamiento por nombre o slug
- Selecciona una categoría para la foto
- Sube la imagen (máximo 5MB)

### 2. Subir una foto
1. **Seleccionar alojamiento**: Busca por nombre o slug
2. **Elegir categoría**: Haz clic en el botón de la categoría deseada
3. **Seleccionar archivo**: Haz clic en el área de subida o arrastra la imagen
4. **Subir foto**: Haz clic en "Subir Foto"

### 3. Ver galería
- Las fotos se muestran organizadas por categorías
- Cada foto muestra su fecha de subida
- Opciones para eliminar fotos (funcionalidad simulada en esta versión)

## 🎨 Categorías Disponibles

| Categoría | Icono | Descripción |
|-----------|-------|-------------|
| Salón | 🛋️ | Áreas comunes de estar |
| Cocina | 🍴 | Cocinas y áreas de preparación de alimentos |
| Jardín | 🌳 | Jardines y áreas verdes |
| Habitación | 🛏️ | Dormitorios y áreas de descanso |
| Baño | 🚿 | Baños y áreas de aseo |
| Exterior | ⛰️ | Vistas exteriores y paisajes |
| Piscina | 🏊 | Áreas de piscina |
| Comedor | 🪑 | Áreas de comedor |
| Terraza | ☂️ | Terrazas y áreas al aire libre |
| Otro | ➕ | Otras categorías no especificadas |

## 🔄 Flujo de Trabajo

1. **Usuario selecciona alojamiento** → Busca por nombre/slug
2. **Usuario elige categoría** → Selecciona tipo de foto
3. **Usuario sube imagen** → Selecciona archivo JPG/PNG/WEBP
4. **Sistema procesa imagen** → Convierte a WebP, guarda en carpeta
5. **Sistema actualiza base de datos** → Registra foto en `photo_categories`
6. **Usuario ve resultado** → Foto aparece en galería organizada

## 📊 Beneficios para SEO

### 1. **Estructura de URLs limpia**
- URLs como `/accommodations_images/alojamiento-el-mirador-del-cid/salon-1706367695.webp`
- Fácilmente indexable por motores de búsqueda

### 2. **Formato WebP optimizado**
- Tamaños de archivo reducidos (30-50% más pequeños que JPG)
- Carga más rápida de páginas
- Mejor experiencia de usuario

### 3. **Organización semántica**
- Categorías claras para contenido estructurado
- Fácil navegación para usuarios y bots
- Mejor comprensión del contenido por parte de los motores de búsqueda

### 4. **Nombres de archivo descriptivos**
- Incluyen categoría y son únicos
- Ayudan a los motores de búsqueda a entender el contenido

## 🛠️ Personalización

### Añadir nuevas categorías
Edita el array `$photoCategories` en `api/upload_accommodation_photo.php`:

```php
$photoCategories = [
    'salon' => 'Salón',
    'cocina' => 'Cocina',
    // Añade nuevas categorías aquí
    'nueva-categoria' => 'Nueva Categoría'
];
```

### Cambiar calidad de WebP
Modifica el parámetro de calidad en la función `convertToWebP()`:

```php
imagewebp($image, null, 80); // 80% calidad (0-100)
```

### Cambiar tamaño máximo de archivo
Ajusta la variable `$maxSize` en `api/upload_accommodation_photo.php`:

```php
$maxSize = 5 * 1024 * 1024; // 5MB (ajusta según necesidades)
```

## 🔒 Seguridad

- Validación de tipos de archivo (solo imágenes)
- Límite de tamaño de archivo (5MB por defecto)
- Sanitización de entradas
- Autenticación opcional (puede activarse según necesidades)

## 📈 Escalabilidad

- **Almacenamiento organizado**: Cada alojamiento tiene su propia carpeta
- **Base de datos relacional**: Fácil de consultar y mantener
- **Soporte para múltiples fotos**: Sin límites en número de fotos por alojamiento
- **Rendimiento optimizado**: Archivos WebP reducen carga del servidor

## 🎯 Próximos Pasos

1. **Integrar con el formulario existente**: Añadir enlace desde `agregar-alojamiento.html`
2. **Implementar eliminación real**: Crear endpoint para eliminar fotos
3. **Añadir edición de categorías**: Permitir cambiar categoría de fotos existentes
4. **Implementar ordenamiento**: Permitir ordenar fotos dentro de cada categoría
5. **Añadir metadatos**: Información adicional como descripciones o etiquetas

## 📚 Documentación Adicional

- **API Endpoints**:
  - `POST /api/upload_accommodation_photo.php` - Subir foto
  - `GET /api/get_accommodation_photos.php?slug={slug}` - Obtener fotos

- **Código de ejemplo**:
  - `gestion-fotos-alojamiento.html` - Interfaz de usuario
  - `api/upload_accommodation_photo.php` - Lógica de subida y conversión
  - `api/get_accommodation_photos.php` - Lógica de recuperación

## 🤝 Soporte

Para cualquier pregunta o problema con el sistema de gestión de fotos, contacta con:

- **Email**: olgamarin@rutasrurales.io
- **Teléfono**: +34 605 249 696
- **Instagram**: @rutas_rurales

---

**¡Disfruta de tu nuevo sistema de gestión de fotos optimizado para SEO y rendimiento!** 🚀