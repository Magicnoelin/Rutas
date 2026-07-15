\[ Imagen del Evento \] (cultural\_events.hero\_image)

│

├───► ¿Tiene valor? ──────► SÍ ──► Se muestra esta imagen.

│

└───► ¿Es NULL/Vacio? ────► NO ──► Busca la imagen de su Categoría.

│

└───► (categories\_events.hero\_image)



1. \*\*Prioridad 1 (Especificidad):\*\* Si el evento tiene su propia imagen configurada en \`cultural\_events.hero\_image\`, se mostrará esta portada.

2. \*\*Prioridad 2 (Herencia):\*\* Si dejas el campo en blanco (\`NULL\`) en el evento, el sistema cargará de forma automática la imagen configurada en la categoría correspondiente (\`categories\_events.hero\_image\`).


---


\#\# 🗄️ Dónde se guardan en la Base de Datos


Ambas tablas comparten una estructura similar para este propósito:


\#\#\# 1. Tabla de Categorías (\`categories\_events\`)

\*   \*\*Columna:\*\* \`hero\_image\`

\*   \*\*Tipo:\*\* \`varchar(500)\` (Permite URLs largas o rutas de archivos).

\*   \*\*Función:\*\* Imagen de portada por defecto para todos los eventos asociados a esta categoría (por ejemplo: \*Teatro, Senderismo, Gastronomía\*).

\*   \*\*Comentario en BD:\*\* \*\\"Imagen hero por defecto para todos los eventos de la categoría\\"\*


\#\#\# 2. Tabla de Eventos (\`cultural\_events\`)

\*   \*\*Columna:\*\* \`hero\_image\`

\*   \*\*Tipo:\*\* \`varchar(500)\` (Admite valores nulos: \`YES\`).

\*   \*\*Función:\*\* Imagen específica para ese evento concreto.

\*   \*\*Valor por defecto:\*\* \`NULL\` (activa la herencia de la categoría).

\*   \*\*Comentario en BD:\*\* \*\\"Imagen hero específica del evento. NULL = heredar de la categoría\\"\*


---


\#\# 🛠️ Cómo rellenar el campo (Formatos válidos)


Dependiendo de cómo esté estructurado el almacenamiento de archivos de tu servidor, en el campo \`hero\_image\` puedes poner:


1. \*\*Rutas Relativas (Recomendado para orden interno):\*\*

   Si las fotos se suben a una carpeta de tu hosting:

   \`\`\`text

   /assets/img/hero/eventos/mi-evento-especial.jpg

URLs Absolutas (Para imágenes externas):

Si utilizas un servidor de almacenamiento externo o CDN:


Plaintext

\[https://rutasrurales.io/uploads/images/portada-evento.webp\](https://rutasrurales.io/uploads/images/portada-evento.webp)

✍️ Consultas SQL Útiles de Referencia

A. Ver qué imágenes tienen asignadas los eventos y sus categorías:

SQL

SELECT 

    e.id AS evento\_id,

    e.name AS evento,

    e.hero\_image AS imagen\_evento,

    c.name AS categoria,

    c.hero\_image AS imagen\_categoria

FROM cultural\_events e

LEFT JOIN categories\_events c ON e.parent\_id = c.id; -- ajusta la columna de relación si varía

B. Actualizar la imagen de un evento específico:

SQL

UPDATE cultural\_events 

SET hero\_image = '/assets/img/hero/eventos/fiesta-pueblo.jpg' 

WHERE id = 123;

C. Quitar la imagen a un evento para que vuelva a heredar de la categoría:

SQL

UPDATE cultural\_events 

SET hero\_image = NULL 

WHERE id = 123;

Documento de referencia para el desarrollo y mantenimiento de Rutas Rurales.

"""


with open("guia\_imagenes\_hero.md", "w", encoding="utf-8") as f:

f.write(content)



¡Por supuesto! He creado un archivo Markdown (\`.md\`) bien estructurado para que puedas guardarlo en tu equipo o en la carpeta de tu proyecto. En él se detalla de forma clara la lógica de herencia (cómo un evento "hereda" la foto de su categoría si se deja en blanco), la estructura de la base de datos y un par de consultas SQL útiles para cuando necesites actualizar los datos.




\#\#\# 📝 Resumen del contenido que incluye el archivo:


\* \*\*Esquema visual de herencia:\*\* Muestra cómo el sistema toma la imagen de \`cultural\_events\` y, si es \`NULL\` (vacío), hace el \*fallback\* automático a \`categories\_events\`.

\* \*\*Detalle de las tablas:\*\* Los nombres exactos de las columnas, tipos de datos (\`varchar(500)\`) y comentarios internos de tu base de datos \`u412199647\_Rutas\`.

\* \*\*Formatos de ruta recomendados:\*\* Ejemplos de rutas relativas (ej. \`/assets/img/...\`) y absolutas.

\* \*\*Chuleta de consultas SQL:\*\* Ejemplos prácticos listos para copiar y pegar si necesitas:

  1. Consultar de un vistazo qué eventos tienen imagen propia y cuáles heredan de la categoría.

  2. Forzar a un evento a que vuelva a heredar la imagen usando \`SET hero\_image = NULL\`. 


Puedes guardarlo en tu entorno de desarrollo (como en Visual Studio Code) para tenerlo siempre a mano. ¡Espero que te sea de gran utilidad para el futuro desarrollo de \*\*Rutas Rurales\*\*!
