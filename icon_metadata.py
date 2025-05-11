import os
import json
import glob

def create_combined_json():
    # Path to icons directory
    icons_dir = "icons/"
    
    # List to store all JSON data
    all_items = []
    
    # Find all JSON files in the icons directory
    # Only get files with .json extension
    json_files = glob.glob(os.path.join(icons_dir, "*.json"))
    
    # Skip items.json if it already exists
    json_files = [f for f in json_files if os.path.basename(f) != "items.json"]
    
    # Process each JSON file
    for json_file in json_files:
        try:
            with open(json_file, 'r') as f:
                data = json.load(f)
                all_items.append(data)
        except Exception as e:
            print(f"Error processing {json_file}: {e}")
    
    # Write the combined data to items.json
    output_path = os.path.join(icons_dir, "items.json")
    with open(output_path, 'w') as f:
        json.dump(all_items, f, indent=2)  # Fixed: added the file object 'f'
    
    print(f"Successfully created {output_path} with {len(all_items)} items")

if __name__ == "__main__":
    create_combined_json()