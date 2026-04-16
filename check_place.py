#!/usr/bin/env python3
import json
import sys

def main():
    try:
        with open('places_of_interest.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Check structure
        if 'places_of_interest' in data:
            places = data['places_of_interest']
        else:
            places = data  # Assume it's already an array
        
        print(f"Total places in JSON: {len(places)}")
        
        # Search for "Collado" in name
        collado_places = []
        for place in places:
            name = place.get('name', '').lower()
            slug = place.get('slug', '').lower()
            municipality = place.get('municipality', '').lower()
            
            if 'collado' in name or 'collado' in slug:
                collado_places.append(place)
        
        print(f"\nFound {len(collado_places)} places with 'Collado' in name or slug:")
        for place in collado_places:
            print(f"  - Name: {place.get('name')}")
            print(f"    Slug: {place.get('slug')}")
            print(f"    Municipality: {place.get('municipality')}")
            print(f"    Province: {place.get('province')}")
            print(f"    Active: {place.get('is_active')}")
            print()
        
        # Search for "Berninches" in municipality
        berninches_places = []
        for place in places:
            municipality = place.get('municipality', '').lower()
            if 'berninches' in municipality:
                berninches_places.append(place)
        
        print(f"\nFound {len(berninches_places)} places in municipality 'Berninches':")
        for place in berninches_places:
            print(f"  - Name: {place.get('name')}")
            print(f"    Slug: {place.get('slug')}")
            print(f"    Municipality: {place.get('municipality')}")
            print(f"    Province: {place.get('province')}")
            print(f"    Active: {place.get('is_active')}")
            print()
        
        # Search for the specific slug
        target_slug = 'ermita-de-nuestra-señora-del-collado-berninches'
        target_slug_encoded = 'ermita-de-nuestra-se%C3%B1ora-del-collado-berninches'
        
        found = None
        for place in places:
            slug = place.get('slug', '')
            if slug == target_slug or slug == target_slug_encoded:
                found = place
                break
        
        if found:
            print(f"\nFound place with slug '{target_slug}':")
            print(f"  Name: {found.get('name')}")
            print(f"  Slug: {found.get('slug')}")
            print(f"  Municipality: {found.get('municipality')}")
            print(f"  Province: {found.get('province')}")
            print(f"  Active: {found.get('is_active')}")
        else:
            print(f"\nNo place found with slug '{target_slug}' or '{target_slug_encoded}'")
            
            # Try partial match
            print("\nSearching for places with 'ermita' and 'collado' in name...")
            partial_matches = []
            for place in places:
                name = place.get('name', '').lower()
                if 'ermita' in name and 'collado' in name:
                    partial_matches.append(place)
            
            if partial_matches:
                print(f"Found {len(partial_matches)} partial matches:")
                for place in partial_matches:
                    print(f"  - Name: {place.get('name')}")
                    print(f"    Slug: {place.get('slug')}")
                    print(f"    Municipality: {place.get('municipality')}")
                    print(f"    Province: {place.get('province')}")
            else:
                print("No partial matches found.")
        
        # Check active vs inactive places
        active_count = sum(1 for p in places if p.get('is_active') == 1)
        inactive_count = len(places) - active_count
        print(f"\nActive places: {active_count}, Inactive places: {inactive_count}")
        
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

if __name__ == '__main__':
    main()