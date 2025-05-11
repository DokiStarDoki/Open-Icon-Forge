import os

folder_path = "./raw_pngs"  # Replace with your actual folder path
output_file = "icon_names.txt"

icon_names = [os.path.splitext(f)[0] for f in os.listdir(folder_path) if f.lower().endswith(".png")]

# Write with UTF-8 encoding to handle all characters
with open(os.path.join(folder_path, output_file), "w", encoding="utf-8") as file:
    for name in icon_names:
        file.write(name + "\n")

print(f"Saved {len(icon_names)} icon names to {output_file}")
