#!/usr/bin/env python3
"""
Script para analizar traducciones faltantes de eventos culturales
Compara eventos en sitemap-eventos.xml con traducciones en cultural_events_trads
"""

import re
import mysql.connector
from mysql.connector import Error
import xml.etree.ElementTree as ET

def conectar_bd():
    """Conectar a la base de datos"""
    try:
        # Configuración de la base de datos (tomada de api/config.php)
        connection = mysql.connector.connect(
            host='localhost',
            database='u412199647_Rutas',
            user='u412199647_olgamarin',
            password='Rutas5Rurales7$'
        )
        
        if connection.is_connected():
            print("Conexión a BD exitosa")
            return connection
            
    except Error as e:
        print(f"Error de conexión: {e}")
        return None

def extraer_slugs_sitemap():
    """Extraer slugs del sitemap-eventos.xml"""
    try:
        with open('sitemap-eventos.xml', 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        # Buscar todos los slugs en el sitemap
        patron = r'evento/([^<"]+)'
        slugs = re.findall(patron, contenido)
        slugs_unicos = list(set(slugs))
        
        print(f"Total slugs en sitemap-eventos.xml: {len(slugs_unicos)}")
        return slugs_unicos
        
    except Exception as e:
        print(f"Error leyendo sitemap: {e}")
        return []

def analizar_traducciones_faltantes():
    """Analizar qué eventos necesitan traducciones"""
    connection = conectar_bd()
    if not connection:
        return
    
    cursor = connection.cursor(dictionary=True)
    
    # 1. Extraer slugs del sitemap
    slugs_sitemap = extraer_slugs_sitemap()
    
    # 2. Para cada slug, obtener el ID del evento
    eventos_con_ids = {}
    eventos_sin_ids = []
    
    for slug in slugs_sitemap:
        slug = slug.strip()
        query = "SELECT id, name, slug, start_date, province FROM cultural_events WHERE slug = %s AND is_active = 1"
        cursor.execute(query, (slug,))
        resultado = cursor.fetchone()
        
        if resultado:
            eventos_con_ids[resultado['id']] = {
                'id': resultado['id'],
                'name': resultado['name'],
                'slug': resultado['slug'],
                'start_date': resultado['start_date'],
                'province': resultado['province']
            }
        else:
            eventos_sin_ids.append(slug)
    
    print(f"Eventos encontrados en BD: {len(eventos_con_ids)}")
    print(f"Eventos no encontrados en BD: {len(eventos_sin_ids)}")
    
    if eventos_sin_ids:
        print("\nSlugs no encontrados en BD:")
        for slug in eventos_sin_ids[:10]:  # Mostrar solo los primeros 10
            print(f"  - {slug}")
        if len(eventos_sin_ids) > 10:
            print(f"  ... y {len(eventos_sin_ids) - 10} más")
    
    # 3. Verificar traducciones para cada evento encontrado
    idiomas = ['en', 'fr', 'de', 'zh']
    resultados = {}
    
    for event_id, evento in eventos_con_ids.items():
        resultados[event_id] = {
            'evento': evento,
            'traducciones': {}
        }
        
        for idioma in idiomas:
            query = "SELECT COUNT(*) as count FROM cultural_events_trads WHERE event_id = %s AND language_code = %s"
            cursor.execute(query, (event_id, idioma))
            resultado = cursor.fetchone()
            
            resultados[event_id]['traducciones'][idioma] = (resultado['count'] > 0)
    
    # 4. Analizar resultados
    eventos_completos = 0
    eventos_incompletos = 0
    eventos_sin_traducciones = 0
    
    eventos_faltantes_por_idioma = {
        'en': 0,
        'fr': 0,
        'de': 0,
        'zh': 0
    }
    
    for event_id, data in resultados.items():
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
    
    print("\n=== RESUMEN DE TRADUCCIONES ===")
    print(f"Total eventos analizados: {len(resultados)}")
    print(f"Eventos con TODAS las traducciones (en, fr, de, zh): {eventos_completos}")
    print(f"Eventos con ALGUNAS traducciones: {eventos_incompletos}")
    print(f"Eventos SIN traducciones: {eventos_sin_traducciones}")
    
    print("\n=== TRADUCCIONES FALTANTES POR IDIOMA ===")
    for idioma in idiomas:
        print(f"{idioma.upper()}: {eventos_faltantes_por_idioma[idioma]} eventos faltantes")
    
    # 5. Mostrar eventos que necesitan traducciones
    print("\n=== EVENTOS QUE NECESITAN TRADUCCIONES ===")
    eventos_para_traducir = []
    
    for event_id, data in resultados.items():
        traducciones = data['traducciones']
        faltantes = []
        
        for idioma in idiomas:
            if not traducciones[idioma]:
                faltantes.append(idioma)
        
        if faltantes:
            eventos_para_traducir.append({
                'id': event_id,
                'name': data['evento']['name'],
                'slug': data['evento']['slug'],
                'start_date': data['evento']['start_date'],
                'province': data['evento']['province'],
                'faltantes': faltantes
            })
    
    # Ordenar por fecha
    eventos_para_traducir.sort(key=lambda x: x['start_date'] if x['start_date'] else '')
    
    print(f"\nTotal eventos que necesitan traducciones: {len(eventos_para_traducir)}\n")
    
    for evento in eventos_para_traducir[:20]:  # Mostrar solo los primeros 20
        print(f"ID: {evento['id']}")
        print(f"Nombre: {evento['name']}")
        print(f"Slug: {evento['slug']}")
        print(f"Fecha: {evento['start_date']}")
        print(f"Provincia: {evento['province']}")
        print(f"Idiomas faltantes: {', '.join(evento['faltantes'])}")
        print("---")
    
    if len(eventos_para_traducir) > 20:
        print(f"... y {len(eventos_para_traducir) - 20} eventos más")
    
    # 6. Generar SQL para insertar traducciones faltantes
    print("\n=== SQL PARA INSERTAR TRADUCCIONES FALTANTES ===")
    generar_sql_traducciones(eventos_para_traducir, cursor, connection)
    
    cursor.close()
    connection.close()

def generar_sql_traducciones(eventos_para_traducir, cursor, connection):
    """Generar SQL para insertar traducciones faltantes"""
    idiomas = ['en', 'fr', 'de', 'zh']
    
    for idioma in idiomas:
        print(f"\n-- TRADUCCIONES PARA {idioma}")
        
        eventos_faltantes_idioma = [e for e in eventos_para_traducir if idioma in e['faltantes']]
        
        if not eventos_faltantes_idioma:
            print(f"-- No hay eventos faltantes para {idioma}")
            continue
        
        for evento in eventos_faltantes_idioma:
            # Obtener datos del evento para generar traducción
            event_id = evento['id']
            query = "SELECT name, slug, venue_name, province, start_date, end_date FROM cultural_events WHERE id = %s"
            cursor.execute(query, (event_id,))
            row = cursor.fetchone()
            
            if row:
                nombre = row['name'].replace("'", "''")
                slug_base = row['slug'].replace("'", "''")
                lugar = row['venue_name'].replace("'", "''") if row['venue_name'] else ''
                provincia = row['province'].replace("'", "''") if row['province'] else ''
                fecha_inicio = row['start_date']
                fecha_fin = row['end_date']
                
                # Generar slug traducido
                sufijo = get_sufijo_slug(idioma)
                slug_traducido = slug_base + sufijo
                
                # Generar contenido traducido
                contenido = generar_contenido_traducido(idioma, nombre, lugar, provincia, fecha_inicio, fecha_fin)
                
                # Escapar comillas simples en el contenido
                for key in contenido:
                    if contenido[key]:
                        contenido[key] = contenido[key].replace("'", "''")
                
                print(f"INSERT INTO cultural_events_trads (event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description) VALUES ")
                print(f"({event_id}, '{idioma}', '{nombre}', '{slug_traducido}', '{contenido['short_description']}', '{contenido['description']}', '{contenido['program']}', '{contenido['target_audience']}', '{contenido['accessibility']}', '{contenido['meta_title']}', '{contenido['meta_description']}');")

def get_sufijo_slug(idioma):
    """Obtener sufijo para slug según idioma"""
    sufijos = {
        'en': '-traditional-festival-spain',
        'fr': '-fete-traditionnelle-espagne',
        'de': '-traditionelles-fest-spanien',
        'zh': '-chuantongjieri-xibanya'
    }
    
    return sufijos.get(idioma, '')

def generar_contenido_traducido(idioma, nombre, lugar, provincia, fecha_inicio, fecha_fin):
    """Generar contenido traducido según idioma"""
    fecha_texto = str(fecha_inicio) if fecha_inicio else ''
    if fecha_fin and fecha_fin != fecha_inicio:
        fecha_texto += f" to {fecha_fin}"
    
    contenidos = {
        'en': {
            'short_description': f"Traditional festival in {lugar}, {provincia} featuring local culture, music, and traditions.",
            'description': f"""<p>The {nombre} is one of the most important traditional festivals in {provincia}, Spain. This annual celebration brings together locals and visitors to experience authentic Spanish culture.</p>
<p>Highlights include:</p>
<ul>
<li>Traditional music and dance performances</li>
<li>Local gastronomy and food stalls</li>
<li>Cultural exhibitions and workshops</li>
<li>Family-friendly activities</li>
<li>Religious processions (if applicable)</li>
</ul>
<p>Dates: {fecha_texto}</p>
<p>Location: {lugar}, {provincia}, Spain</p>""",
            'program': 'Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.',
            'target_audience': 'International tourists, culture enthusiasts, families',
            'accessibility': 'Wheelchair accessible, family-friendly, multilingual information available',
            'meta_title': f"{nombre} | Traditional Festival in Spain",
            'meta_description': f"Experience the {nombre} in {lugar}, {provincia}. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists."
        },
        'fr': {
            'short_description': f"Fête traditionnelle à {lugar}, {provincia} mettant en valeur la culture locale, la musique et les traditions.",
            'description': f"""<p>Le {nombre} est l'une des fêtes traditionnelles les plus importantes de {provincia}, Espagne. Cette célébration annuelle réunit habitants et visiteurs pour vivre une expérience authentique de la culture espagnole.</p>
<p>Points forts :</p>
<ul>
<li>Spectacles de musique et danse traditionnelles</li>
<li>Gastronomie locale et stands de nourriture</li>
<li>Expositions et ateliers culturels</li>
<li>Activités familiales</li>
<li>Processions religieuses (le cas échéant)</li>
</ul>
<p>Dates : {fecha_texto}</p>
<p>Lieu : {lugar}, {provincia}, Espagne</p>""",
            'program': "Programme quotidien incluant activités matinales, événements culturels l'après-midi et célébrations nocturnes avec musique et spectacles traditionnels.",
            'target_audience': 'Touristes internationaux, amateurs de culture, familles',
            'accessibility': 'Accessible aux fauteuils roulants, adapté aux familles, informations multilingues disponibles',
            'meta_title': f"{nombre} | Fête Traditionnelle en Espagne",
            'meta_description': f"Vivez le {nombre} à {lugar}, {provincia}. Fête traditionnelle espagnole avec activités culturelles, nourriture locale et célébrations authentiques. Parfait pour les touristes internationaux."
        },
        'de': {
            'short_description': f"Traditionelles Fest in {lugar}, {provincia} mit lokaler Kultur, Musik und Traditionen.",
            'description': f"""<p>Das {nombre} ist eines der wichtigsten traditionellen Feste in {provincia}, Spanien. Diese jährliche Feier bringt Einheimische und Besucher zusammen, um authentische spanische Kultur zu erleben.</p>
<p>Höhepunkte:</p>
<ul>
<li>Traditionelle Musik- und Tanzvorführungen</li>
<li>Lokale Gastronomie und Essensstände</li>
<li>Kulturausstellungen und Workshops</li>
<li>Familienfreundliche Aktivitäten</li>
<li>Religiöse Prozessionen (falls zutreffend)</li>
</ul>
<p>Daten: {fecha_texto}</p>
<p>Ort: {lugar}, {provincia}, Spanien</p>""",
            'program': 'Tagesprogramm beinhaltet morgendliche Aktivitäten, nachmittägliche Kulturveranstaltungen und abendliche Feiern mit Musik und traditionellen Darbietungen.',
            'target_audience': 'Internationale Touristen, Kulturliebhaber, Familien',
            'accessibility': 'Rollstuhlgerecht, familienfreundlich, mehrsprachige Informationen verfügbar',
            'meta_title': f"{nombre} | Traditionelles Fest in Spanien",
            'meta_description': f"Erleben Sie das {nombre} in {lugar}, {provincia}. Traditionelles spanisches Fest mit kulturellen Aktivitäten, lokaler Küche und authentischen Feiern. Perfekt für internationale Touristen."
        },
        'zh': {
            'short_description': f"西班牙{provincia} {lugar}的传统节日，展示当地文化、音乐和传统。",
            'description': f"""<p>{nombre}是西班牙{provincia}最重要的传统节日之一。这个年度庆典汇聚了当地居民和游客，共同体验地道的西班牙文化。</p>
<p>亮点包括：</p>
<ul>
<li>传统音乐和舞蹈表演</li>
<li>当地美食和小吃摊</li>
<li>文化展览和工作坊</li>
<li>适合家庭的活动</li>
<li>宗教游行（如适用）</li>
</ul>
<p>日期：{fecha_texto}</p>
<p>地点：西班牙{provincia} {lugar}</p>""",
            'program': '每日行程包括上午活动、下午文化活动和晚间庆祝活动，配有音乐和传统表演。',
            'target_audience': '国际游客, 文化爱好者, 家庭',
            'accessibility': '轮椅通道, 适合家庭, 提供多语言信息',
            'meta_title': f"{nombre} | 西班牙传统节日",
            'meta_description': f"体验西班牙{provincia} {lugar}的{nombre}。西班牙传统节日，包含文化活动、当地美食和地道庆祝。非常适合国际游客。"
        }
    }
    
    return contenidos.get(idioma, contenidos['en'])

if __name__ == "__main__":
    analizar_traducciones_faltantes()