import os
import glob

# Buscar y reemplazar en todos los archivos HTML
for html_file in glob.glob('**/*.html', recursive=True):
    with open(html_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if 'Logo0transparente' in content:
        new_content = content.replace('Logo0transparente', 'Logo%20transparente')
        with open(html_file, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Corregido: {html_file}')

print('\n¡Listo! Todos los logos han sido corregidos.')
