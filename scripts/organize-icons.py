import os
import shutil
import json
from datetime import datetime

# CONFIGURATION
INCOMING_DIR = 'incoming'              # Folder where new SVGs and input.json are placed
INPUT_JSON = os.path.join(INCOMING_DIR, 'input.json')  # Input JSON file path
OUTPUT_METADATA = 'metadata.json'       # Central metadata file
ICONS_DIR = 'icons'                     # Organized icons folder

# Load new icon entries
with open(INPUT_JSON, 'r', encoding='utf-8') as f:
    new_icons = json.load(f)

# Load existing metadata if it exists
if os.path.exists(OUTPUT_METADATA):
    with open(OUTPUT_METADATA, 'r', encoding='utf-8') as f:
        all_metadata = json.load(f)
else:
    all_metadata = []

# Ensure base icons directory exists
os.makedirs(ICONS_DIR, exist_ok=True)

# Process each new icon
for icon in new_icons:
    svg_filename = icon['file']
    theme = icon['theme']

    # Create theme folder if it doesn't exist
    theme_folder = os.path.join(ICONS_DIR, theme.replace(' ', '_').lower())
    os.makedirs(theme_folder, exist_ok=True)

    # Paths
    src_svg_path = os.path.join(INCOMING_DIR, svg_filename)
    dst_svg_path = os.path.join(theme_folder, svg_filename)

    # Move SVG file
    if os.path.exists(src_svg_path):
        shutil.move(src_svg_path, dst_svg_path)
        print(f"Moved: {src_svg_path} -> {dst_svg_path}")
    else:
        print(f"WARNING: SVG file not found: {src_svg_path}")
        continue  # Skip adding to metadata if SVG is missing

    # Update file path in metadata
    icon['file'] = dst_svg_path.replace('\\', '/').replace(' ', '_')

    # Add current date if missing
    if 'date_created' not in icon or not icon['date_created']:
        icon['date_created'] = datetime.now().strftime('%Y-%m-%d')

    # Add to metadata list
    all_metadata.append(icon)

# Save updated metadata
with open(OUTPUT_METADATA, 'w', encoding='utf-8') as f:
    json.dump(all_metadata, f, indent=2, ensure_ascii=False)

print("\n✅ All icons processed and metadata updated!")
