# Photo library files

Each photo has two files: `<key>.webp` (max 1600 px wide, about 350 KB or less) and `<key>-sm.webp`
(800 px, about 150 KB or less). Register every key in `../../../photo-library.php`.

- Keys: lowercase words with hyphens that say what is in the photo (`harbour-sunrise`, `room-garden-view-2`).
- Strip EXIF and GPS before adding a photo: a phone photo can reveal where the owner lives.
- Real photos only for named places, rooms, products and people. People need their consent.
- AI scenery: key starts with `ai-`; list it in `docs/data/photos/AI_IMAGES.md` with the reason.
- Import with the skill `wp-content` (photo pipeline): resize, convert, dedupe, write the registry entry.
