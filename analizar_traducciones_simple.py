#!/usr/bin/env python3
"""
Script simplificado para analizar traducciones faltantes usando solo sitemaps
"""

import re
import os
from datetime import datetime

def analizar_sitemaps():
    """Analizar sitemaps para identificar traducciones faltantes"""
    
    print("=== ANÁLISIS DE TRADUCCIONES FALTANTES ===\n")
    
    # Verificar si existen los sitemaps
    sitemap_es = 'sitemap-eventos.xml'
    sitemap_i18n = 'sitemap-eventos-i18n.xml'
    
    if not os.path.exists(sitemap_es):
        print(f"ERROR: No se encuentra {sitemap_es}")
        return
    
    if not os.path.exists(sitemap_i18n):
        print(f"ERROR: No se encuentra {sitemap_i18n}")
        return
    
    # 1. Leer eventos en español
    with open(sitemap_es, 'r', encoding='utf-8') as f:
        contenido_es = f.read()
    
    # Extraer slugs de eventos en español
    patron_es = r'evento/([^<"]+)'
    slugs_es = re.findall(patron_es, contenido_es)
    slugs_es = list(set(slugs_es))  # Eliminar duplicados
    
    print(f"Total eventos en español: {len(slugs_es)}")
    
    # 2. Leer eventos traducidos (i18n)
    with open(sitemap_i18n, 'r', encoding='utf-8') as f:
        contenido_i18n = f.read()
    
    # Extraer traducciones por idioma
    patron_i18n = r'/(en|fr|de|zh)/evento/([^<"]+)'
    matches = re.findall(patron_i18n, contenido_i18n)
    
    traducciones_por_idioma = {
        'en': set(),
        'fr': set(),
        'de': set(),
        'zh': set()
    }
    
    for idioma, slug in matches:
        if idioma in traducciones_por_idioma:
            traducciones_por_idioma[idioma].add(slug)
    
    print("\nTraducciones existentes por idioma:")
    for idioma in ['en', 'fr', 'de', 'zh']:
        print(f"  {idioma.upper()}: {len(traducciones_por_idioma[idioma])}")
    
    # 3. Analizar traducciones faltantes
    print("\n=== ANÁLISIS POR EVENTO ===")
    
    eventos_con_traducciones = 0
    eventos_sin_traducciones = 0
    eventos_parciales = 0
    
    for slug_es in slugs_es[:50]:  # Analizar solo primeros 50 para no saturar
        traducciones_evento = []
        
        for idioma in ['en', 'fr', 'de', 'zh']:
            tiene_traduccion = False
            
            # Buscar si hay alguna traducción que contenga el slug base
            slug_base = slug_es.lower()
            for trad_slug in traducciones_por_idioma[idioma]:
                if slug_base in trad_slug.lower():
                    tiene_traduccion = True
                    break
            
            if tiene_traduccion:
                traducciones_evento.append(idioma)
        
        num_traducciones = len(traducciones_evento)
        
        if num_traducciones == 4:
            eventos_con_traducciones += 1
        elif num_traducciones == 0:
            eventos_sin_traducciones += 1
        else:
            eventos_parciales += 1
    
    print(f"\nResumen (primeros 50 eventos):")
    print(f"  Eventos con 4 traducciones: {eventos_con_traducciones}")
    print(f"  Eventos con traducciones parciales: {eventos_parciales}")
    print(f"  Eventos sin traducciones: {eventos_sin_traducciones}")
    
    # 4. Generar recomendaciones
    print("\n=== RECOMENDACIONES ===")
    print("1. Ejecutar el script SQL: actualizar_traducciones_eventos.sql")
    print("2. Usar las credenciales de la base de datos:")
    print("   - Host: localhost")
    print("   - DB: u412199647_Rutas")
    print("   - User: u412199647_olgamarin")
    print("   - Pass: Rutas5Rurales7$")
    print("\n3. Para ejecutar el SQL:")
    print("   mysql -u u412199647_olgamarin -p u412199647_Rutas < actualizar_traducciones_eventos.sql")
    print("\n4. Después de actualizar, regenerar sitemap:")
    print("   php generar-sitemap-eventos-i18n.php")

def main():
    """Función principal"""
    print("Analizando traducciones faltantes...")
    analizar_sitemaps()

if __name__ == "__main__":
    main()