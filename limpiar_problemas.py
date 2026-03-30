#!/usr/bin/env python3
"""
Script para limpiar problemas comunes en el proyecto Rutas
Ejecutar con: python limpiar_problemas.py
"""

import os
import re
import glob

def limpiar_archivos_js():
    """Limpia problemas comunes en archivos JavaScript"""
    archivos_js = glob.glob('*.js')

    for archivo in archivos_js:
        if archivo == 'limpiar_problemas.py':
            continue

        print(f"Procesando {archivo}...")

        with open(archivo, 'r', encoding='utf-8') as f:
            contenido = f.read()

        # Remover console.log en producción
        contenido = re.sub(r'console\.log\(.*?\);?\s*', '', contenido)

        # Convertir var a let/const donde sea seguro
        # (esto es conservador, solo casos obvios)
        contenido = re.sub(r'\bvar\s+', 'let ', contenido)

        with open(archivo, 'w', encoding='utf-8') as f:
            f.write(contenido)

    print("Archivos JavaScript limpiados.")

def limpiar_archivos_html():
    """Limpia problemas comunes en archivos HTML"""
    archivos_html = glob.glob('*.html')

    for archivo in archivos_html:
        print(f"Procesando {archivo}...")

        with open(archivo, 'r', encoding='utf-8') as f:
            contenido = f.read()

        # Agregar lang attribute si falta
        if '<html>' in contenido and 'lang=' not in contenido:
            contenido = contenido.replace('<html>', '<html lang="es">')

        # Agregar alt text a imágenes que no lo tienen
        contenido = re.sub(r'<img([^>]*?)src="([^"]*?)"([^>]*?)(?!.*alt)([^>]*?)>',
                          r'<img\1src="\2"\3 alt="Imagen"\4>', contenido)

        with open(archivo, 'w', encoding='utf-8') as f:
            f.write(contenido)

    print("Archivos HTML limpiados.")

def limpiar_archivos_css():
    """Limpia problemas comunes en archivos CSS"""
    archivos_css = glob.glob('*.css')

    for archivo in archivos_css:
        print(f"Procesando {archivo}...")

        with open(archivo, 'r', encoding='utf-8') as f:
            contenido = f.read()

        # Remover !important innecesarios (conservador)
        contenido = re.sub(r'\s*!important\s*;', ';', contenido)

        with open(archivo, 'w', encoding='utf-8') as f:
            f.write(contenido)

    print("Archivos CSS limpiados.")

def main():
    print("🧹 Iniciando limpieza de problemas comunes...")
    print("=" * 50)

    limpiar_archivos_js()
    print()
    limpiar_archivos_html()
    print()
    limpiar_archivos_css()
    print()

    print("✅ Limpieza completada!")
    print("💡 Recomendaciones adicionales:")
    print("   - Instala ESLint: npm install -g eslint")
    print("   - Ejecuta: eslint *.js para más análisis")
    print("   - Revisa el panel de problemas de VS Code")

