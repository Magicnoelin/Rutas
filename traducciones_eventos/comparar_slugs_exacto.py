#!/usr/bin/env python3
"""
Script para comparar slugs exactos entre sitemap español y sitemap i18n
"""

import re

def extraer_slugs_es():
    """Extraer slugs exactos del sitemap español"""
    try:
        with open('sitemap-eventos.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar slugs exactos
        patron = r'<loc>https://rutasrurales\.io/evento/([^<]+)</loc>'
        slugs = re.findall(patron, contenido)
        
        print(f"Slugs en sitemap español: {len(slugs)}")
        return set(slugs)
        
    except Exception as e:
        print(f"Error: {e}")
        return set()

def extraer_slugs_i18n():
    """Extraer slugs exactos del sitemap i18n por idioma"""
    try:
        with open('sitemap-eventos-i18n.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar slugs por idioma
        patron = r'<loc>https://rutasrurales\.io/(en|fr|de|zh)/evento/([^<]+)</loc>'
        matches = re.findall(patron, contenido)
        
        slugs_por_idioma = {
            'en': set(),
            'fr': set(),
            'de': set(),
            'zh': set()
        }
        
        slugs_todos = set()
        
        for idioma, slug in matches:
            slugs_por_idioma[idioma].add(slug)
            slugs_todos.add(slug)
        
        print(f"Slugs únicos en sitemap i18n: {len(slugs_todos)}")
        for idioma in ['en', 'fr', 'de', 'zh']:
            print(f"  {idioma.upper()}: {len(slugs_por_idioma[idioma])}")
        
        return slugs_por_idioma, slugs_todos
        
    except Exception as e:
        print(f"Error: {e}")
        return {'en': set(), 'fr': set(), 'de': set(), 'zh': set()}, set()

def analizar_correspondencia(slugs_es, slugs_i18n_todos):
    """Analizar correspondencia entre slugs"""
    print("\n" + "="*60)
    print("ANÁLISIS DE CORRESPONDENCIA ENTRE SLUGS")
    print("="*60)
    
    # Slugs en español que NO están en i18n (ninguna versión)
    slugs_sin_correspondencia = []
    
    for slug_es in slugs_es:
        tiene_correspondencia = False
        
        # Buscar si hay algún slug en i18n que contenga el slug base
        slug_base = slug_es.lower()
        # Remover año si existe
        slug_base_sin_año = re.sub(r'-\d{4}$', '', slug_base)
        
        for slug_i18n in slugs_i18n_todos:
            slug_i18n_lower = slug_i18n.lower()
            
            # Verificar correspondencias
            if (slug_base in slug_i18n_lower or 
                slug_base_sin_año in slug_i18n_lower or
                any(part in slug_i18n_lower for part in slug_base.split('-'))):
                tiene_correspondencia = True
                break
        
        if not tiene_correspondencia:
            slugs_sin_correspondencia.append(slug_es)
    
    print(f"\nSlugs en español sin correspondencia en i18n: {len(slugs_sin_correspondencia)}")
    
    if slugs_sin_correspondencia:
        print("\nPrimeros 20 slugs sin correspondencia:")
        for i, slug in enumerate(slugs_sin_correspondencia[:20]):
            print(f"  {i+1}. {slug}")
        
        if len(slugs_sin_correspondencia) > 20:
            print(f"  ... y {len(slugs_sin_correspondencia) - 20} más")
    
    return slugs_sin_correspondencia

def generar_sql_para_slugs_faltantes(slugs_faltantes):
    """Generar SQL para slugs faltantes"""
    if not slugs_faltantes:
        print("\nNo hay slugs faltantes para generar SQL.")
        return
    
    print("\n" + "="*60)
    print("SQL PARA SLUGS FALTANTES")
    print("="*60)
    
    # Tomar 5 ejemplos
    ejemplos = slugs_faltantes[:5]
    
    for slug in ejemplos:
        print(f"\n-- Para evento con slug: {slug}")
        print("-- En la tabla cultural_events, buscar el ID correspondiente:")
        print(f"SELECT id, name, slug, start_date, province FROM cultural_events WHERE slug = '{slug}' AND is_active = 1;")
        print()
        print("-- Luego insertar traducciones (ejemplo para inglés):")
        print("INSERT INTO cultural_events_trads (event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)")
        print("SELECT ")
        print("  id,")
        print("  'en',")
        print("  name,")
        print("  CONCAT(slug, '-traditional-festival-spain-2026'),")
        print("  CONCAT('Traditional festival in ', venue_name, ', ', province, ' featuring local culture, music, and traditions.'),")
        print("  CONCAT('<p>The ', name, ' is one of the most important traditional festivals in ', province, ', Spain...</p>'),")
        print("  'Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.',")
        print("  'International tourists, culture enthusiasts, families',")
        print("  'Wheelchair accessible, family-friendly, multilingual information available',")
        print("  CONCAT(name, ' | Traditional Festival in Spain'),")
        print("  CONCAT('Experience the ', name, ' in ', venue_name, ', ', province, '. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.')")
        print(f"FROM cultural_events WHERE slug = '{slug}' AND is_active = 1;")

def main():
    """Función principal"""
    print("Comparando slugs exactos entre sitemaps...")
    
    # Extraer slugs
    slugs_es = extraer_slugs_es()
    slugs_por_idioma, slugs_i18n_todos = extraer_slugs_i18n()
    
    if not slugs_es:
        print("Error: No se pudieron extraer slugs del sitemap español")
        return
    
    # Analizar correspondencia
    slugs_faltantes = analizar_correspondencia(slugs_es, slugs_i18n_todos)
    
    # Generar SQL si hay faltantes
    generar_sql_para_slugs_faltantes(slugs_faltantes)
    
    # Estadísticas finales
    print("\n" + "="*60)
    print("ESTADÍSTICAS FINALES")
    print("="*60)
    print(f"Total eventos en español: {len(slugs_es)}")
    print(f"Total slugs únicos en i18n: {len(slugs_i18n_todos)}")
    print(f"Eventos sin traducciones detectadas: {len(slugs_faltantes)}")
    
    if slugs_faltantes:
        print("\nRECOMENDACIÓN:")
        print("1. Ejecutar primero las consultas SELECT para verificar que los eventos existen en cultural_events")
        print("2. Luego ejecutar los INSERT correspondientes para cada idioma faltante")
        print("3. Usar el script generar_traducciones_eventos_futuros.sql para automatizar el proceso")
    else:
        print("\n¡Todos los eventos parecen tener traducciones!")

if __name__ == "__main__":
    main()