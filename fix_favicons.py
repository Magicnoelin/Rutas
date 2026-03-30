import os
import glob

# Buscar y reemplazar en todos los archivos HTML
for html_file in glob.glob('**/*.html', recursive=True):
    with open(html_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if 'href="favicon.png"' in content:
        new_content = content.replace('href="favicon.png"', 'href="menu_images/Favicon.png"')
        with open(html_file, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Corregido: {html_file}')

print('\n¡Listo! Todos los favicons han sido actualizados.')
