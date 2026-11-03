<?php
/**
 * Photo library registry: one entry per photo in assets/img/photos/.
 *
 * Files: <key>.webp (max 1600 px wide) and <key>-sm.webp (800 px), EXIF/GPS stripped.
 * alt: what is in the photo, in plain words, max ~12 words; "" only for decorative photos.
 * AI images: key starts with "ai-" and is listed in docs/data/photos/AI_IMAGES.md.
 * Print one with {{PREFIX}}_photo( 'key', array( 'sizes' => '(min-width: 900px) 50vw, 100vw' ) ).
 *
 * Example entry (scripts/photos/photos.py import prints these lines for you):
 *     'harbour-sunrise' => array( 'alt' => 'Fishing boats in the harbour at sunrise', 'w' => 1600, 'h' => 1067, 'credit' => 'owner' ),
 *
 * @package {{PREFIX_UC}}_Child
 */

return array();
