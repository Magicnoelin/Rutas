#!/usr/bin/env python3
"""
Script simple para analizar traducciones faltantes comparando sitemaps
"""

import re

def extraer_slugs_espanol():
    """Extraer slugs del sitemap-eventos.xml (español)"""
    try:
        with open('sitemap-eventos.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar todos los slugs en el sitemap
        patron = r'evento/([^<"]+)'
        slugs = re.findall(patron, contenido)
        slugs_unicos = list(set(slugs))
        
        print(f"Total slugs en sitemap-eventos.xml (español): {len(slugs_unicos)}")
        return slugs_unicos
        
    except Exception as e:
        print(f"Error leyendo sitemap español: {e}")
        return []

def extraer_slugs_i18n():
    """Extraer slugs del sitemap-eventos-i18n.xml (traducciones)"""
    try:
        with open('sitemap-eventos-i18n.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar todos los slugs en el sitemap i18n
        # Los slugs tienen formato: /de/evento/slug-traducido o /en/evento/slug-traducido
        patron = r'/(en|fr|de|zh)/evento/([^<"]+)'
        matches = re.findall(patron, contenido)
        
        # Organizar por idioma
        slugs_por_idioma = {
            'en': [],
            'fr': [],
            'de': [],
            'zh': []
        }
        
        for idioma, slug in matches:
            if idioma in slugs_por_idioma:
                slugs_por_idioma[idioma].append(slug)
        
        # Hacer únicos
        for idioma in slugs_por_idioma:
            slugs_por_idioma[idioma] = list(set(slugs_por_idioma[idioma]))
        
        print(f"\nTotal slugs en sitemap-eventos-i18n.xml:")
        for idioma in ['en', 'fr', 'de', 'zh']:
            print(f"  {idioma.upper()}: {len(slugs_por_idioma[idioma])}")
        
        return slugs_por_idioma
        
    except Exception as e:
        print(f"Error leyendo sitemap i18n: {e}")
        return {'en': [], 'fr': [], 'de': [], 'zh': []}

def analizar_traducciones():
    """Analizar qué slugs en español tienen traducciones"""
    slugs_es = extraer_slugs_espanol()
    slugs_i18n = extraer_slugs_i18n()
    
    if not slugs_es:
        print("No se pudieron extraer slugs en español")
        return
    
    print("\n=== ANÁLISIS DE TRADUCCIONES ===")
    
    # Para cada slug en español, verificar si tiene traducciones
    # Nota: Esto es una aproximación porque los slugs traducidos pueden ser diferentes
    # Buscamos slugs base (sin sufijos como -traditional-festival-spain)
    
    resultados = {}
    idiomas = ['en', 'fr', 'de', 'zh']
    
    for slug_es in slugs_es:
        resultados[slug_es] = {
            'traducciones': {},
            'slug_base': slug_es
        }
        
        # Intentar encontrar el slug base (sin año si está al final)
        slug_base = slug_es
        # Remover año al final (ej: -2026)
        if slug_base.endswith('-2026'):
            slug_base = slug_base[:-5]
        
        for idioma in idiomas:
            # Buscar si hay algún slug traducido que contenga el slug base
            tiene_traduccion = False
            for slug_trad in slugs_i18n[idioma]:
                # Verificar si el slug traducido contiene el slug base
                # o si el slug base contiene parte del slug traducido
                if slug_base in slug_trad or any(part in slug_trad for part in slug_es.split('-')):
                    tiene_traduccion = True
                    break
            
            resultados[slug_es]['traducciones'][idioma] = tiene_traduccion
    
    # Analizar resultados
    eventos_completos = 0
    eventos_incompletos = 0
    eventos_sin_traducciones = 0
    
    eventos_faltantes_por_idioma = {
        'en': 0,
        'fr': 0,
        'de': 0,
        'zh': 0
    }
    
    for slug_es, data in resultados.items():
        traducciones = data['traducciones']
        count_traducciones = sum(traducciones.values())
        
        if count_traducciones == 4:
            eventos_completos += 1
        elif count_traducciones > 0:
            eventos_incompletos += 1
        else:
            eventos_sin_traducciones += 1
        
        # Contar faltantes por idioma
        for idioma in idiomas:
            if not traducciones[idioma]:
                eventos_faltantes_por_idioma[idioma] += 1
    
    print(f"\nTotal eventos analizados: {len(resultados)}")
    print(f"Eventos con TODAS las traducciones (en, fr, de, zh): {eventos_completos}")
    print(f"Eventos con ALGUNAS traducciones: {eventos_incompletos}")
    print(f"Eventos SIN traducciones: {eventos_sin_traducciones}")
    
    print("\n=== TRADUCCIONES FALTANTES POR IDIOMA ===")
    for idioma in idiomas:
        print(f"{idioma.upper()}: {eventos_faltantes_por_idioma[idioma]} eventos faltantes")
    
    # Mostrar eventos que necesitan traducciones
    print("\n=== EVENTOS QUE NECESITAN TRADUCCIONES (primeros 20) ===")
    eventos_para_traducir = []
    
    for slug_es, data in resultados.items():
        traducciones = data['traducciones']
        faltantes = []
        
        for idioma in idiomas:
            if not traducciones[idioma]:
                faltantes.append(idioma)
        
        if faltantes:
            eventos_para_traducir.append({
                'slug': slug_es,
                'faltantes': faltantes
            })
    
    print(f"\nTotal eventos que necesitan traducciones: {len(eventos_para_traducir)}\n")
    
    for i, evento in enumerate(eventos_para_traducir[:20]):
        print(f"{i+1}. {evento['slug']}")
        print(f"   Idiomas faltantes: {', '.join(evento['faltantes'])}")
    
    if len(eventos_para_traducir) > 20:
        print(f"\n... y {len(eventos_para_traducir) - 20} eventos más")
    
    # Generar SQL de ejemplo basado en el script SQL existente
    print("\n=== EJEMPLO DE SQL PARA TRADUCCIONES ===")
    print("-- Basado en completar_traducciones_eventos_final.sql")
    print("-- Los siguientes son ejemplos de cómo se insertarían traducciones:")
    
    # Tomar algunos ejemplos
    ejemplos = eventos_para_traducir[:3]
    for ejemplo in ejemplos:
        slug = ejemplo['slug']
        print(f"\n-- Para evento con slug: {slug}")
        
        for idioma in ejemplo['faltantes'][:2]:  # Mostrar solo 2 idiomas por ejemplo
            sufijos = {
                'en': '-traditional-festival-spain',
                'fr': '-fete-traditionnelle-espagne',
                'de': '-traditionelles-fest-spanien',
                'zh': '-chuantongjieri-xibanya'
            }
            
            sufijo = sufijos.get(idioma, '')
            slug_traducido = slug + sufijo
            
            print(f"-- {idioma.upper()}: INSERT INTO cultural_events_trads ...")
            print(f"--   Slug traducido: {slug_traducido}")
    
    print("\n=== RECOMENDACIONES ===")
    print("1. Ejecutar el script SQL existente: completar_traducciones_eventos_final.sql")
    print("2. Verificar que todos los eventos activos tengan traducciones para en, fr, de, zh")
    print("3. Regenerar sitemap-eventos-i18n.xml después de insertar traducciones")
    print("4. Los slugs traducidos deben seguir el patrón: slug-original + sufijo-idioma")

if __name__ == "__main__":
    analizar_traducciones()