from PIL import Image
from pathlib import Path

def trim_white_margins(png_path: Path, padding: int = 10):
    img = Image.open(png_path).convert("RGB")
    bg_color = (255, 255, 255)

    # Create a mask of non-white areas
    mask = Image.eval(img, lambda px: 0 if px == 255 else 255).convert("L")
    bbox = mask.getbbox()

    if bbox and bbox != (0, 0, img.width, img.height):
        # Expand the box by padding, but keep it within image bounds
        left = max(bbox[0] - padding, 0)
        upper = max(bbox[1] - padding, 0)
        right = min(bbox[2] + padding, img.width)
        lower = min(bbox[3] + padding, img.height)

        trimmed = img.crop((left, upper, right, lower))
        trimmed.save(png_path)
        print(f"✅ Trimmed with padding: {png_path.name}")
    else:
        print(f"⏩ No visible whitespace to trim: {png_path.name}")

def batch_trim_white(folder: str, padding: int = 10):
    path = Path(folder)
    for file in path.glob("*.png"):
        trim_white_margins(file, padding)

# Example usage
if __name__ == "__main__":
    batch_trim_white("input_pngs", padding=10)
