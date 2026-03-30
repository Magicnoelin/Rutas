import re

# 1. Quitar el bloque del widget embebido en index.html
content = open('index.html','r',encoding='utf-8').read()

# Eliminar desde el comentario ANTONIO WIDGET hasta el </script> que le sigue
content = re.sub(
    r'\s*<!-- ={10,}\s*ANTONIO WIDGET.*?</script>\s*\n',
    '\n',
    content,
    flags=re.DOTALL
)

open('index.html','w',encoding='utf-8').write(content)
print('index.html: widget embebido eliminado, size:', len(content))

# 2. En script.js, asegurarse de que el widget se añade al body con z-index máximo
# y que NO se crea si ya existe (ya tiene esa comprobación)
content_js = open('script.js','r',encoding='utf-8').read()

# Buscar donde se hace document.body.appendChild(widget) y añadir z-index forzado después
old = 'document.body.appendChild(widget);'
new = '''document.body.appendChild(widget);
    // Forzar z-index máximo después de insertar
    var wEl = document.getElementById('antonio-widget');
    if (wEl) {
        wEl.style.setProperty('position','fixed','important');
        wEl.style.setProperty('z-index','2147483647','important');
        wEl.style.setProperty('bottom','20px','important');
        wEl.style.setProperty('right','20px','important');
    }'''

if old in content_js:
    content_js = content_js.replace(old, new, 1)
    open('script.js','w',encoding='utf-8').write(content_js)
    print('script.js: z-index forzado añadido, size:', len(content_js))
else:
    print('script.js: no se encontro appendChild, buscando alternativa...')
    idx = content_js.find('body.appendChild')
    print('body.appendChild at:', idx)
    print(content_js[max(0,idx-50):idx+100])
