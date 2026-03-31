#!/usr/bin/env python3
import mysql.connector
from datetime import datetime

# Database configuration
config = {
    'host': 'localhost',
    'database': 'u412199647_Rutas',
    'user': 'u412199647_olgamarin',
    'password': 'Rutas5Rurales7$',
    'charset': 'utf8mb4'
}

try:
    # Connect to the database
    connection = mysql.connector.connect(**config)
    cursor = connection.cursor(dictionary=True)
    
    print("=" * 80)
    print("CULTURAL EVENTS ANALYSIS")
    print("=" * 80)
    
    # 1. Check structure of cultural_events table
    print("\n1. STRUCTURE OF cultural_events TABLE")
    print("-" * 40)
    cursor.execute("DESCRIBE cultural_events")
    columns = cursor.fetchall()
    print(f"{'Field':<20} {'Type':<20} {'Null':<10} {'Key':<10} {'Default':<15} {'Extra':<10}")
    print("-" * 80)
    for col in columns:
        print(f"{col['Field']:<20} {col['Type']:<20} {col['Null']:<10} {col['Key']:<10} {str(col['Default']):<15} {col['Extra']:<10}")
    
    # 2. Get active events after April 1, 2026
    print("\n\n2. ACTIVE CULTURAL EVENTS (is_active=1) AFTER APRIL 1, 2026")
    print("-" * 40)
    query = """
        SELECT * FROM cultural_events 
        WHERE is_active = 1 
        AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
        ORDER BY start_date ASC
    """
    cursor.execute(query)
    events = cursor.fetchall()
    
    print(f"Found {len(events)} active events after April 1, 2026")
    print("\nEvents:")
    print(f"{'ID':<5} {'Name':<40} {'Start Date':<12} {'End Date':<12} {'Location':<20} {'Slug':<30}")
    print("-" * 120)
    for event in events:
        print(f"{event['id']:<5} {event['name'][:38]:<40} {str(event['start_date']):<12} {str(event['end_date']):<12} {event['location'][:18]:<20} {event['slug'][:28]:<30}")
    
    # 3. Check existing translations for these events
    print("\n\n3. EXISTING TRANSLATIONS FOR ACTIVE EVENTS")
    print("-" * 40)
    
    all_languages = ['es', 'en', 'fr', 'de', 'zh']
    
    for event in events:
        event_id = event['id']
        query = f"""
            SELECT language_code, COUNT(*) as count 
            FROM cultural_events_trads 
            WHERE event_id = {event_id} 
            GROUP BY language_code
        """
        cursor.execute(query)
        translations = cursor.fetchall()
        
        print(f"\nEvent ID {event_id}: {event['name'][:50]}")
        print(f"{'Language':<10} {'Count':<10}")
        print("-" * 20)
        
        existing_languages = []
        for trans in translations:
            print(f"{trans['language_code']:<10} {trans['count']:<10}")
            existing_languages.append(trans['language_code'])
        
        # Check missing languages
        missing_languages = [lang for lang in all_languages if lang not in existing_languages]
        if missing_languages:
            print(f"Missing languages: {', '.join(missing_languages)}")
    
    # 4. Check for empty or incomplete translations
    print("\n\n4. INCOMPLETE TRANSLATIONS (EMPTY FIELDS)")
    print("-" * 40)
    
    query = """
        SELECT cet.*, ce.name as original_name, ce.slug as original_slug 
        FROM cultural_events_trads cet
        JOIN cultural_events ce ON cet.event_id = ce.id
        WHERE ce.is_active = 1 
        AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
        AND (cet.name = '' OR cet.slug = '' OR cet.short_description = '' OR cet.description = '' 
             OR cet.program = '' OR cet.target_audience = '' OR cet.meta_title = '' OR cet.meta_description = '')
    """
    cursor.execute(query)
    incomplete = cursor.fetchall()
    
    print(f"Found {len(incomplete)} incomplete translations")
    print(f"\n{'ID':<5} {'Event ID':<10} {'Language':<10} {'Original Name':<40} {'Empty Fields':<50}")
    print("-" * 115)
    
    for row in incomplete:
        empty_fields = []
        if not row['name'] or row['name'].strip() == '':
            empty_fields.append('name')
        if not row['slug'] or row['slug'].strip() == '':
            empty_fields.append('slug')
        if not row['short_description'] or row['short_description'].strip() == '':
            empty_fields.append('short_description')
        if not row['description'] or row['description'].strip() == '':
            empty_fields.append('description')
        if not row['program'] or row['program'].strip() == '':
            empty_fields.append('program')
        if not row['target_audience'] or row['target_audience'].strip() == '':
            empty_fields.append('target_audience')
        if not row['meta_title'] or row['meta_title'].strip() == '':
            empty_fields.append('meta_title')
        if not row['meta_description'] or row['meta_description'].strip() == '':
            empty_fields.append('meta_description')
        
        print(f"{row['id']:<5} {row['event_id']:<10} {row['language_code']:<10} {row['original_name'][:38]:<40} {', '.join(empty_fields)[:48]:<50}")
    
    # 5. Check for missing translation records entirely
    print("\n\n5. MISSING TRANSLATION RECORDS (NO ENTRY IN cultural_events_trads)")
    print("-" * 40)
    
    for event in events:
        event_id = event['id']
        query = f"""
            SELECT language_code FROM cultural_events_trads 
            WHERE event_id = {event_id}
        """
        cursor.execute(query)
        existing_langs = [row['language_code'] for row in cursor.fetchall()]
        
        missing_langs = [lang for lang in all_languages if lang not in existing_langs]
        if missing_langs:
            print(f"Event ID {event_id} ({event['name'][:50]}): Missing {', '.join(missing_langs)}")
    
    print("\n" + "=" * 80)
    print("ANALYSIS COMPLETE")
    print("=" * 80)
    
except mysql.connector.Error as err:
    print(f"Database error: {err}")
except Exception as e:
    print(f"Error: {e}")
finally:
    if 'cursor' in locals():
        cursor.close()
    if 'connection' in locals():
        connection.close()