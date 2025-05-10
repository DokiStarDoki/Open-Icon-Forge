import os
import json
import shutil
from PIL import Image
import subprocess
import numpy as np
from tqdm import tqdm
from pathlib import Path

def create_directories(base_path):
    """Create necessary subdirectories if they don't exist."""
    for folder in ['trimmed', 'bmp', 'svg', 'finished']:
        folder_path = Path(base_path) / folder
        folder_path.mkdir(parents=True, exist_ok=True)

def trim_image(input_path, output_path):
    """Trim transparent pixels and whitespace from PNG and save to output path."""
    with Image.open(input_path) as img:
        # Convert to RGBA for transparency
        if img.mode != 'RGBA':
            img = img.convert('RGBA')
        
        # Trim transparent pixels
        bbox = img.getbbox()
        if bbox:
            img = img.crop(bbox)
        
        # Trim whitespace using NumPy
        img_rgb = img.convert('RGB')
        img_array = np.array(img_rgb)
        # Non-white pixels: any channel < 250
        non_white = (img_array < 200).any(axis=2)
        if non_white.any():
            # Find rows and columns with non-white pixels
            rows, cols = np.where(non_white)
            left, right = cols.min(), cols.max() + 1
            top, bottom = rows.min(), rows.max() + 1
            img = img.crop((left, top, right, bottom))
        
        img.save(output_path, 'PNG')

def process_pngs(input_dir, trimmed_dir):
    """Process all PNGs in input directory, trimming and saving to trimmed directory."""
    png_files = [f for f in os.listdir(input_dir) if f.lower().endswith('.png')]
    for filename in tqdm(png_files, desc="Trimming PNGs"):
        input_path = Path(input_dir) / filename
        output_path = Path(trimmed_dir) / filename
        try:
            trim_image(input_path, output_path)
        except Exception as e:
            print(f"Error trimming {filename}: {e}")

def convert_to_bmp(trimmed_dir, bmp_dir):
    """Convert trimmed PNGs to black-and-white BMPs with contrast normalization."""
    png_files = [f for f in os.listdir(trimmed_dir) if f.lower().endswith('.png')]
    for filename in tqdm(png_files, desc="Converting to BMPs"):
        input_path = Path(trimmed_dir) / filename
        bmp_filename = os.path.splitext(filename)[0] + '.bmp'
        output_path = Path(bmp_dir) / bmp_filename
        try:
            with Image.open(input_path) as img:
                img = img.convert('RGB')
                img_array = np.array(img)
                luma = (0.299 * img_array[:, :, 0] + 
                        0.587 * img_array[:, :, 1] + 
                        0.114 * img_array[:, :, 2])
                min_luma = np.min(luma)
                max_luma = np.max(luma)
                luma_range = max(1, max_luma - min_luma)
                normalized = (luma - min_luma) * (255 / luma_range)
                threshold = 128
                bw_array = np.where(normalized < threshold, 0, 255).astype(np.uint8)
                bw_img = Image.fromarray(bw_array, mode='L')
                bw_img.save(output_path, 'BMP')
        except Exception as e:
            print(f"Error converting {filename} to BMP: {e}")

def convert_to_svg(bmp_dir, svg_dir):
    """Convert BMPs to SVGs using potrace."""
    bmp_files = [f for f in os.listdir(bmp_dir) if f.lower().endswith('.bmp')]
    for filename in tqdm(bmp_files, desc="Converting to SVGs"):
        input_path = Path(bmp_dir) / filename
        svg_filename = os.path.splitext(filename)[0] + '.svg'
        output_path = Path(svg_dir) / svg_filename
        try:
            subprocess.run(['potrace', str(input_path), '-b', 'svg', '-o', str(output_path)], check=True)
        except subprocess.CalledProcessError as e:
            print(f"Error converting {filename} to SVG: {e}")
        except FileNotFoundError:
            print("Potrace not found. Ensure potrace is installed and in PATH.")
            return

def process_metadata(input_dir, svg_dir, finished_dir):
    """Match SVGs with metadata using 'key', create JSON files and rename SVGs using 'name'."""
    json_path = Path(input_dir) / 'new-items.json'
    print(f"Checking for JSON file: {json_path}")
    if not json_path.exists():
        print("new-items.json not found in input directory.")
        return
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            metadata = json.load(f)
        print(f"Loaded metadata with {len(metadata)} entries")
    except json.JSONDecodeError as e:
        print(f"Error reading new-items.json: {e}")
        return
    
    # Check write permissions for finished_dir
    try:
        test_file = Path(finished_dir) / 'test.txt'
        with open(test_file, 'w') as f:
            f.write('test')
        test_file.unlink()
        print(f"Write permissions confirmed for {finished_dir}")
    except Exception as e:
        print(f"Error: Cannot write to {finished_dir}: {e}")
        return
    
    svg_files = [f for f in os.listdir(svg_dir) if f.lower().endswith('.svg')]
    print(f"Found {len(svg_files)} SVG files: {svg_files}")
    for filename in tqdm(svg_files, desc="Processing metadata"):
        key_name = os.path.splitext(filename)[0].strip()
        print(f"Processing SVG: {filename} (key: {key_name!r})")
        matched = False
        for item in metadata:
            metadata_key = item.get('key', '').strip()
            print(f"Checking metadata key: {metadata_key!r}")
            if metadata_key.lower() == key_name.lower():
                matched = True
                try:
                    name = item.get('name', key_name).strip()
                    print(f"Matched! Creating JSON and SVG for name: {name}")
                    json_filename = f"{name}.json"
                    json_path = Path(finished_dir) / json_filename
                    with open(json_path, 'w', encoding='utf-8') as f:
                        json.dump(item, f, indent=4)
                    print(f"Created JSON: {json_path}")
                    svg_path = Path(svg_dir) / filename
                    new_svg_filename = f"{name}.svg"
                    new_svg_path = Path(finished_dir) / new_svg_filename
                    shutil.move(str(svg_path), str(new_svg_path))
                    print(f"Moved SVG to: {new_svg_path}")
                except Exception as e:
                    print(f"Error processing {filename}: {e}")
                break
        if not matched:
            print(f"No metadata match found for {filename}")

def main():
    input_dir = 'input/raw_pngs'
    input_path = Path(input_dir)
    if not input_path.exists():
        print("Input directory not found.")
        return
    
    trimmed_dir = input_path.parent / 'trimmed'
    bmp_dir = input_path.parent / 'bmp'
    svg_dir = input_path.parent / 'svg'
    finished_dir = input_path.parent / 'finished'
    create_directories(input_path.parent)
    
    process_pngs(input_dir, trimmed_dir)
    convert_to_bmp(trimmed_dir, bmp_dir)
    convert_to_svg(bmp_dir, svg_dir)
    process_metadata(input_dir, svg_dir, finished_dir)
    
    print("Processing complete.")

if __name__ == '__main__':
    main()