# Photos: where they come from and where they go

1. **Originals** (the owner's phone exports and shoots) are kept outside git, in a backed-up folder. List it in `docs/reference/DISASTER_RECOVERY.md`.
2. **Review** a new batch: `python3 -I scripts/photos/photos.py review <folder>` writes `<date>-review.csv` here (duplicates, sizes, already in library).
3. **Decide** per photo and write the manifest `<date>-import-manifest.csv` with columns `group,file,key,alt` (alt `-` marks a decorative photo; it gets `alt=""`):
   - `IMPORT`: a real photo you may use.
   - `HOLD-people`: shows people who haven't agreed. Ask first.
   - `HOLD-licence`: a stock or unknown photo. Find the licence first.
   - `SKIP`: duplicate, blurry, or off-topic.
4. **Import:** `python3 -I scripts/photos/photos.py import <manifest> --source <folder> --dry-run`, then run it without `--dry-run`. Paste the printed lines into the theme's `photo-library.php`.
5. **Use** in templates through `{{PREFIX}}_photo( 'key' )`. Use at most two photos per section unless the design says otherwise.

AI images: list every `ai-*` key in `AI_IMAGES.md` with the reason and the date.
