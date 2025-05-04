# Open Icon Forge

An open-source, AI-powered system for generating clean black-and-white SVG icons — organized by theme, structured for growth, and built for creators.

## 🔍 What Is This?

**Open Icon Forge** is a modular icon pipeline and viewer system. It enables users to browse, ingest, vectorize, and finalize themed SVG icons — all semi-automated with AI support, but manual-first for quality control.

Ideal for designers, developers, educators, and hobbyists who want structured, scalable icon sets.

## ⚙️ Core Features

- ✅ Free and open-source
- 🤖 AI-assisted but manual-first pipeline
- 🎨 Clean, crisp SVGs (black and white)
- 🗂️ Theme-based folder structure
- 📄 Per-icon JSON metadata
- 🔍 Tag and theme filtering
- 🖼️ Visual dashboard for ingesting, reviewing, and managing icons

## 🧭 Dashboard Overview

Open `quick-start.html` in your browser to use the dashboard. It includes four key steps:

1. **📁 Theme Ingestion**  
   Adds new theme folders from `input/theme.json`.

2. **🖼️ Icon Ingestion**  
   Reads `input/icons.json` and creates placeholder files for icons (SVGs added later).

3. **🎨 Vectorization**  
   Converts PNGs in `/input/` to SVGs using a two-step GD + Potrace process.

4. **🔄 Match + Finalize**  
   Matches generated SVGs to metadata files, places them into the correct folders, and deletes input PNGs.

## 🗃️ Folder Structure

```
/icons/
    /theme-name/
        icon-name.svg      # The icon file
        icon-name.json     # Per-icon metadata

/input/
    theme.json           # New themes to create
    icons.json           # New icon metadata to ingest
    *.png               # PNGs for vectorization

/php/                   # Server-side ingestion/vectorization scripts
/scripts/
    organize_icons.py   # Moves SVG + JSON to correct folders

metadata.json           # Global tracker (used themes, icon list)
dashboard.json          # UI-state tracker (backlogs, selections, generation state)
/index.html             # Visual browser for all finalized icons
/quick-start.html       # Multi-step dashboard for managing icon pipeline

README.md               # You're here!
LICENSE                 # CC0 1.0 Universal (Public Domain Dedication)
```

## 🧠 Metadata Format

### metadata.json

Lightweight tracker used for indexing themes and listing all created icons:

```json
{
  "themes_used": ["Space", "Underwater"],
  "icons_created": [
    {
      "file": "icons/space/space-station.svg",
      "name": "Space Station",
      "theme": "Space"
    }
  ]
}
```

### icons.json (input)

```json
[
  {
    "name": "Space Station",
    "short_description": "A simple satellite-style space station icon",
    "tags": ["space", "station", "satellite", "orbit"],
    "theme": "Space",
    "date_created": "2025-04-28"
  }
]
```

### Per-Icon JSON (/icons/[theme]/[name].json)

Same as above — once finalized, the icon gets its own .json beside the .svg:

```json
{
  "name": "Space Station",
  "short_description": "A simple satellite-style space station icon",
  "tags": ["space", "station", "satellite", "orbit"],
  "theme": "Space",
  "date_created": "2025-04-28"
}
```

## 🖼️ Browse Final Icons

Open `index.html` to browse all finalized icons:

- 🔍 Search by name, tag, or theme
- 🧩 Filter by tag or theme
- 📝 View metadata details
- ⬇ Download SVGs

## Python Script: organize_icons.py

Use this to:

- Move icons and metadata from /input/ into their respective /icons/[theme]/ folder
- Update metadata.json automatically

## 🧪 Tech Stack

- HTML/CSS/JS + GSAP for UI
- PHP for ingestion and file handling
- GD + Potrace for vectorization
- JSON for all metadata storage

## 📄 License

All icons are released under CC0 1.0 Universal (Public Domain Dedication).
Use them freely — no attribution required.