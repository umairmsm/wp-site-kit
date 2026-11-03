#!/usr/bin/env python3
"""Photo library tool: review a new batch for duplicates, then import the approved photos.

  python3 -I scripts/photos/photos.py review <folder>
      Writes docs/data/photos/<date>-review.csv: size, duplicate groups (perceptual hash),
      photos already in the library, and a suggestion per file. Changes nothing else.

  python3 -I scripts/photos/photos.py import <manifest.csv> --source <folder> [--dry-run]
      For rows with group=IMPORT: fix rotation, strip EXIF/GPS, write <key>.webp (max 1600 px)
      and <key>-sm.webp (800 px) into the theme's assets/img/photos/, and print the lines to
      add to photo-library.php. Never overwrites an existing file.

Manifest columns: group,file,key,alt  (group: IMPORT, HOLD-people, HOLD-licence, SKIP;
alt "-" = a decorative photo, imported with alt="")
Needs Pillow with WebP support (pip install pillow).
"""
import csv
import datetime
import io
import pathlib
import sys

try:
    from PIL import Image, ImageOps
except ImportError:
    sys.exit("Pillow is missing: pip install pillow")

ROOT = pathlib.Path(__file__).resolve().parents[2]
LIBRARY = ROOT / "wordpress" / "{{SLUG}}" / "web" / "wp-content" / "themes" / "{{SLUG}}-child" / "assets" / "img" / "photos"
REVIEWS = ROOT / "docs" / "data" / "photos"
IMAGE_TYPES = {".jpg", ".jpeg", ".png", ".webp", ".heic", ".tif", ".tiff"}
SAME_PICTURE_BITS = 22  # of 256: at or below this, two files show the same picture
MIN_WIDTH = 1200
SIZES = (("", 1600, 350_000), ("-sm", 800, 150_000))  # suffix, max width, byte budget


def dhash(img):
    """256-bit difference hash: survives resizing, recompression and small edits."""
    small = img.convert("L").resize((17, 16), Image.LANCZOS)
    px = small.tobytes()  # one byte per pixel in mode "L"
    bits = 0
    for row in range(16):
        for col in range(16):
            left, right = px[row * 17 + col], px[row * 17 + col + 1]
            bits = (bits << 1) | (1 if left > right else 0)
    return bits


def distance(a, b):
    return bin(a ^ b).count("1")


def open_image(path):
    img = Image.open(path)
    return ImageOps.exif_transpose(img)


def review(folder):
    folder = pathlib.Path(folder)
    files = sorted(p for p in folder.rglob("*") if p.suffix.lower() in IMAGE_TYPES)
    library = {}
    for p in sorted(LIBRARY.glob("*.webp")):
        if not p.stem.endswith("-sm"):
            try:
                library[p.stem] = dhash(open_image(p))
            except OSError:
                pass
    rows, seen, groups = [], [], {}
    for p in files:
        name = str(p.relative_to(folder))
        try:
            img = open_image(p)
        except OSError:
            rows.append({"file": name, "px": "", "group": "", "in_library": "", "suggestion": "UNREADABLE"})
            continue
        h = dhash(img)
        group = next((g for g, gh in seen if distance(h, gh) <= SAME_PICTURE_BITS), None)
        if group is None:
            group = len(seen) + 1
            seen.append((group, h))
        row = {"file": name, "px": f"{img.width}x{img.height}", "group": group,
               "in_library": next((k for k, lh in library.items() if distance(h, lh) <= SAME_PICTURE_BITS), ""),
               "suggestion": "", "_pixels": img.width * img.height, "_width": img.width}
        rows.append(row)
        groups.setdefault(group, []).append(row)
    # In each group of the same picture, keep the largest file and mark the rest as duplicates.
    for members in groups.values():
        keeper = max(members, key=lambda r: r["_pixels"])
        for r in members:
            if r["in_library"]:
                r["suggestion"] = "SKIP-in-library"
            elif r is not keeper:
                r["suggestion"] = "SKIP-duplicate"
            elif r["_width"] < MIN_WIDTH:
                r["suggestion"] = "CHECK-small"
            else:
                r["suggestion"] = "IMPORT?"
    for r in rows:
        r.pop("_pixels", None)
        r.pop("_width", None)
    REVIEWS.mkdir(parents=True, exist_ok=True)
    out = REVIEWS / f"{datetime.date.today():%Y-%m-%d}-review.csv"
    with out.open("w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=["file", "px", "group", "in_library", "suggestion"])
        writer.writeheader()
        writer.writerows(rows)
    counts = {}
    for r in rows:
        counts[r["suggestion"]] = counts.get(r["suggestion"], 0) + 1
    print(f"{len(rows)} files -> {out.relative_to(ROOT)}")
    for k, v in sorted(counts.items()):
        print(f"  {k}: {v}")
    print("Next: look at every IMPORT? and CHECK-small photo yourself, then write the manifest (group,file,key,alt).")


def encode(img, width, budget):
    """WebP at the highest quality (80 down to 50) that fits the byte budget."""
    if img.width > width:
        img = img.resize((width, round(img.height * width / img.width)), Image.LANCZOS)
    data = b""
    for quality in (80, 75, 70, 65, 60, 55, 50):
        buf = io.BytesIO()
        img.save(buf, "WEBP", quality=quality, method=6)  # no exif= argument: metadata is dropped
        data = buf.getvalue()
        if len(data) <= budget:
            break
    return img, data


def import_photos(manifest, source, dry_run):
    source = pathlib.Path(source)
    entries, problems = [], 0
    with open(manifest, newline="", encoding="utf-8") as fh:
        for row in csv.DictReader(fh):
            if row.get("group", "").strip() != "IMPORT":
                continue
            key, alt = row["key"].strip(), row["alt"].strip()
            if not key or not key.replace("-", "").isalnum() or key != key.lower():
                print(f"BAD KEY  {row['file']}: '{key}' (lowercase words joined by hyphens)")
                problems += 1
                continue
            if alt == "-":
                alt = ""  # "-" marks a decorative photo on purpose: alt="" (an empty cell is a mistake)
            elif not alt or len(alt.split()) > 12:
                print(f"BAD ALT  {key}: say what is in the photo in 1-12 words, or '-' for a decorative photo")
                problems += 1
                continue
            targets = [LIBRARY / f"{key}{suffix}.webp" for suffix, _, _ in SIZES]
            if any(t.exists() for t in targets):
                print(f"EXISTS   {key}: already in the library, not overwritten (pick the next number, e.g. {key}-2)")
                continue
            try:
                img = open_image(source / row["file"]).convert("RGB")
            except OSError as exc:
                print(f"BAD FILE {row['file']}: {exc}")
                problems += 1
                continue
            written = []
            for (suffix, width, budget), target in zip(SIZES, targets):
                out, data = encode(img, width, budget)
                written.append((target, out.size, len(data)))
                if not dry_run:
                    LIBRARY.mkdir(parents=True, exist_ok=True)
                    target.write_bytes(data)
            full = written[0]
            print(f"{'WOULD' if dry_run else 'WROTE'}    {key}: {full[1][0]}x{full[1][1]}, "
                  + ", ".join(f"{t.name} {n // 1024} KB" for t, _, n in written))
            entries.append((key, alt, full[1]))
    if entries:
        print("\nAdd to photo-library.php:")
        for key, alt, (w, h) in entries:
            safe = alt.replace("\\", "\\\\").replace("'", "\\'")
            print(f"\t'{key}' => array( 'alt' => '{safe}', 'w' => {w}, 'h' => {h}, 'credit' => 'owner' ),")
    return 1 if problems else 0


def main(argv):
    if len(argv) >= 2 and argv[0] == "review":
        review(argv[1])
        return 0
    if len(argv) >= 4 and argv[0] == "import" and argv[2] == "--source":
        return import_photos(argv[1], argv[3], "--dry-run" in argv[4:])
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
