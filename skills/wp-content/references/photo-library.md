# Photo library

## Why a library, not just the Media Library
Site photos (heroes, sections, cards) are part of the design. They are kept in git with the
theme, under stable keys, with their alt text in one registry. Editors still use the Media Library
for featured images and post content. The library also feeds other uses later (social posts,
short videos): one checked set of the business's real photos, each with a description.

## Rules
- **Real-only** for any named place, room, product or person. AI scenery is allowed only as general
  atmosphere, with the key starting `ai-`, logged in `docs/data/photos/AI_IMAGES.md`, and never
  captioned as a specific place.
- **People need consent.** Hold photos with recognisable people until the owner confirms consent.
- **Licences.** Stock, or photos from unknown sources: hold until you can show a licence. Photos on review sites belong to their authors.
- **Privacy.** Strip EXIF and GPS. A phone photo can reveal a home address. The import script re-encodes without metadata.
- **Size.** `<key>.webp` max 1600 px wide (≤ about 350 KB), `<key>-sm.webp` 800 px (≤ about 150 KB).

## Keys and alt text
- Key: lowercase words joined by hyphens that say what is in the photo: `harbour-sunrise`, `room-garden-view`. Variants of the same subject take the next number: `room-garden-view-2`.
- Alt text formula: **what is in the photo**, plus where if it matters, in 1–12 plain words: "Fishing boats in the harbour at sunrise". No "image of", no keyword lists. Variant numbers never appear in alt text.
- Decorative photos (pure background) get `alt=""`. That is deliberate, not missing. In the import manifest, write `-` as the alt to mark one.

## Dedupe
`photos.py review` uses a 256-bit difference hash. At **22 bits or fewer** apart, two files show the same
picture (resized, recompressed, cropped a little). It catches the same photo sent twice by chat
app, by email and from the camera roll. Exact duplicates are 0 bits apart. Always check the groups by eye.
Some formats (HEIC, AVIF) may need extra libraries. The script lists them as UNREADABLE instead of guessing.

## Saving tokens
Hashing and resizing are free, local scripts. Don't spend a large model on "find duplicates".
For "which of these 200 photos are usable", let a cheaper model or a person go through the CSV,
and keep the expensive model for the final decisions.

## After import
- Paste the registry lines into `photo-library.php`. Commit the images and the registry together.
- Add the photo to a template with `<prefix>_photo( 'key', array( 'sizes' => '(min-width: 900px) 50vw, 100vw' ) )`.
- Keep a reuse table in the import report (key → pages using it), so one photo doesn't end up on five pages.
