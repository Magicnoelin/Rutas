#!/usr/bin/env python3
"""
Script para analizar eventos futuros del sitemap que necesitan traducciones
"""

import re
from datetime import datetime

def extraer_eventos_del_sitemap():
    """Extraer eventos del sitemap-eventos.xml"""
    try:
        with open('sitemap-eventos.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar todos los eventos en el sitemap
        # El formato es: <url><loc>https://rutasrurales.io/evento/slug-del-evento</loc></url>
        patron = r'<loc>https://rutasrurales\.io/evento/([^<]+)</loc>'
        slugs = re.findall(patron, contenido)
        
        print(f"Total eventos en sitemap-eventos.xml: {len(slugs)}")
        
        # Filtrar eventos futuros basados en el slug (que contienen año)
        eventos_futuros = []
        hoy = datetime.now()
        
        for slug in slugs:
            # Buscar año en el slug (formato: algo-2026)
            año_match = re.search(r'-(\d{4})(?:-|$)', slug)
            if año_match:
                año = int(año_match.group(1))
                # Si el año es mayor o igual al actual, considerar futuro
                if año >= hoy.year:
                    eventos_futuros.append(slug)
            else:
                # Si no tiene año, asumir que es futuro
                eventos_futuros.append(slug)
        
        print(f"Eventos futuros (basado en slugs con año >= {hoy.year}): {len(eventos_futuros)}")
        return eventos_futuros
        
    except Exception as e:
        print(f"Error leyendo sitemap: {e}")
        return []

def analizar_traducciones_existentes():
    """Analizar qué traducciones ya existen en sitemap-i18n.xml"""
    try:
        with open('sitemap-eventos-i18n.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar traducciones por idioma
        patron = r'<loc>https://rutasrurales\.io/(en|fr|de|zh)/evento/([^<]+)</loc>'
        matches = re.findall(patron, contenido)
        
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
        
        return traducciones_por_idioma
        
    except Exception as e:
        print(f"Error leyendo sitemap i18n: {e}")
        return {'en': set(), 'fr': set(), 'de': set(), 'zh': set()}

def generar_reporte(eventos_futuros, traducciones_por_idioma):
    """Generar reporte de traducciones faltantes"""
    print("\n" + "="*60)
    print("ANÁLISIS DE TRADUCCIONES FALTANTES PARA EVENTOS FUTUROS")
    print("="*60)
    
    # Para cada evento futuro, verificar traducciones
    reporte = []
    idiomas = ['en', 'fr', 'de', 'zh']
    
    for slug in eventos_futuros:
        traducciones = {}
        faltantes = []
        
        for idioma in idiomas:
            tiene_traduccion = False
            # Buscar si hay alguna traducción que contenga el slug base
            slug_base = slug.lower()
            for trad_slug in traducciones_por_idioma[idioma]:
                if slug_base in trad_slug.lower() or any(part in trad_slug.lower() for part in slug_base.split('-')):
                    tiene_traduccion = True
                    break
            
            traducciones[idioma] = tiene_traduccion
            if not tiene_traduccion:
                faltantes.append(idioma)
        
        if faltantes:
            reporte.append({
                'slug': slug,
                'traducciones': traducciones,
                'faltantes': faltantes
            })
    
    print(f"\nTotal eventos futuros analizados: {len(eventos_futuros)}")
    print(f"Eventos futuros que necesitan traducciones: {len(reporte)}")
    
    if not reporte:
        print("\n¡Todos los eventos futuros ya tienen traducciones completas!")
        return
    
    # Estadísticas por idioma
    print("\n" + "="*60)
    print("ESTADÍSTICAS POR IDIOMA")
    print("="*60)
    
    for idioma in idiomas:
        eventos_sin_traduccion = sum(1 for item in reporte if idioma in item['faltantes'])
        print(f"{idioma.upper()}: {eventos_sin_traduccion} eventos sin traducción")
    
    # Mostrar primeros 20 eventos que necesitan traducciones
    print("\n" + "="*60)
    print("EVENTOS FUTUROS QUE NECESITAN TRADUCCIONES (primeros 20)")
    print("="*60)
    
    for i, item in enumerate(reporte[:20]):
        print(f"\n{i+1}. {item['slug']}")
        print(f"   Idiomas faltantes: {', '.join(item['faltantes'])}")
        
        # Sugerir slugs SEO
        print(f"   Sugerencias de slugs SEO:")
        for idioma in item['faltantes']:
            slug_base = item['slug'].lower()
            # Limpiar slug base
            slug_base = re.sub(r'-\d{4}$', '', slug_base)  # Remover año al final
            slug_base = re.sub(r'[^a-z0-9-]', '', slug_base)  # Solo letras, números y guiones
            
            sufijos = {
                'en': f'-traditional-festival-spain-{datetime.now().year}',
                'fr': f'-fete-traditionnelle-espagne-{datetime.now().year}',
                'de': f'-traditionelles-fest-spanien-{datetime.now().year}',
                'zh': f'-chuantongjieri-xibanya-{datetime.now().year}'
            }
            
            sufijo = sufijos.get(idioma, '')
            slug_seo = slug_base + sufijo
            print(f"     {idioma.upper()}: {slug_seo}")
    
    if len(reporte) > 20:
        print(f"\n... y {len(reporte) - 20} eventos más")
    
    # Generar SQL de ejemplo
    print("\n" + "="*60)
    print("EJEMPLO DE SQL PARA INSERTAR TRADUCCIONES")
    print("="*60)
    
    # Tomar 3 ejemplos para mostrar
    ejemplos = reporte[:3]
    for ejemplo in ejemplos:
        slug = ejemplo['slug']
        print(f"\n-- Para evento: {slug}")
        
        for idioma in ejemplo['faltantes'][:2]:  # Mostrar solo 2 idiomas por ejemplo
            print(f"-- {idioma.upper()}:")
            print(f"INSERT INTO cultural_events_trads (event_id, language_code, name, slug, ...)")
            print(f"SELECT id, '{idioma}', name, CONCAT(slug, '-{sufijos[idioma]}'), ...")
            print(f"FROM cultural_events WHERE slug = '{slug}' AND is_active = 1;")
    
    print("\n" + "="*60)
    print("RECOMENDACIONES")
    print("="*60)
    print("1. Ejecutar el script SQL: generar_traducciones_eventos_futuros.sql")
    print("2. Verificar que los slugs generados sean únicos")
    print("3. Los contenidos están optimizados para SEO con:")
    print("   - Títulos H1 y H2 estructurados")
    print("   - Listas con puntos destacados")
    print("   - Información práctica para turistas")
    print("   - Meta titles y descriptions optimizados")
    print("4. Regenerar sitemap-eventos-i18n.xml después de insertar traducciones")

def main():
    """Función principal"""
    print("Analizando sitemaps para identificar eventos futuros que necesitan traducciones...")
    
    # Extraer eventos futuros del sitemap
    eventos_futuros = extraer_eventos_del_sitemap()
    
    if not eventos_futuros:
        print("No se encontraron eventos futuros en el sitemap")
        return
    
    # Analizar traducciones existentes
    traducciones_por_idioma = analizar_traducciones_existentes()
    
    # Generar reporte
    generar_reporte(eventos_futuros, traducciones_por_idioma)

if __name__ == "__main__":
    main()