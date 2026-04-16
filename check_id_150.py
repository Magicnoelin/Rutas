#!/usr/bin/env python3
import json

def main():
    try:
        with open('places_of_interest.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Check structure
        if 'places_of_interest' in data:
            places = data['places_of_interest']
        else:
            places = data  # Assume it's already an array
        
        # Search for ID 150
        place_150 = None
        for place in places:
            if place.get('id') == 150:
                place_150 = place
                break
        
        if place_150:
            print("=== LUGAR CON ID 150 ENCONTRADO ===")
            print(f"ID: {place_150.get('id')}")
            print(f"Slug: {place_150.get('slug')}")
            print(f"Nombre: {place_150.get('name')}")
            print(f"Municipio: {place_150.get('municipality')}")
            print(f"Provincia: {place_150.get('province')}")
            print(f"Activo (is_active): {place_150.get('is_active')}")
            print(f"Descripción corta: {place_150.get('short_description', '')[:100]}...")
            
            # Check if slug matches the problematic one
            target_slug = 'ermita-de-nuestra-señora-del-collado-berninches'
            actual_slug = place_150.get('slug', '')
            
            print(f"\n=== COMPARACIÓN DE SLUGS ===")
            print(f"Slug esperado (en el enlace): {target_slug}")
            print(f"Slug real en BD: {actual_slug}")
            
            if actual_slug != target_slug:
                print(f"\n¡LOS SLUGS NO COINCIDEN!")
                print(f"Esto explica por qué el enlace no funciona.")
                
                # Check URL encoding
                import urllib.parse
                encoded_target = urllib.parse.quote(target_slug, safe='')
                print(f"\nSlug URL-encoded: {encoded_target}")
                print(f"¿Coincide con el real? {actual_slug == encoded_target}")
                
                # Try to find the place by name
                print(f"\n=== BUSCANDO POR NOMBRE SIMILAR ===")
                name = place_150.get('name', '').lower()
                if 'ermita' in name and 'collado' in name:
                    print(f"El nombre contiene 'ermita' y 'collado'")
                    print(f"Nombre completo: {place_150.get('name')}")
                else:
                    print(f"El nombre NO contiene 'ermita' y 'collado'")
                    print(f"Nombre: {place_150.get('name')}")
        else:
            print("No se encontró ningún lugar con ID 150")
            
            # List all IDs to see the range
            ids = [p.get('id') for p in places if p.get('id')]
            print(f"\nIDs encontrados en el archivo: {sorted(ids)}")
            print(f"ID máximo: {max(ids) if ids else 'N/A'}")
            print(f"ID mínimo: {min(ids) if ids else 'N/A'}")
            
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

if __name__ == '__main__':
    main()