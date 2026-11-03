---
name: wp-content
description: Stage 4 of a wp-site-kit WordPress build - real copy and photos. Content truth (facts only from the owner), AI drafts and owner approval, claims audits, banned words and fake proof, legal pages, the photo pipeline (dedupe, real-only, EXIF strip, WebP sizes, alt text, AI image register), and recorded content changes. Use for "write the copy", "add photos", "the owner sent new photos", "check for duplicates", "update prices or text on the live site", "legal pages".
---

# Stage 4: Content

Goal: every public page carries text the owner approved and photos that are real, legal and
light. Nothing on the site claims what the business can't prove.

## Copy steps
1. **Inventory:** list each page and item with what it needs (text, facts, photos) in `docs/data/content-plan.md`.
2. **Facts first:**
   - Facts (prices, inclusions, times, policies, safety notes) come from the owner via `docs/BRIEF.md` or a data file in `docs/data/<type>/`.
   - Missing → `PENDING-<who>` in a flags list (`docs/data/<type>/_SUMMARY.md`). Never invent a fact.
3. **Draft:** AI may draft, but the draft is marked as a draft (a `<prefix>_copy_status` meta, or a list in the content plan) until the owner approves it.
4. **Claims audit** before anything ships (`references/copy-and-claims.md`):
   - every claim is checked against its source;
   - banned words are removed;
   - numbers, awards and reviews need proof.
   If the `claims-auditor` agent is installed, run it.
5. **Owner review on staging, in batches.** Send short messages, each with one question that can be answered with yes or no. Long questionnaires go unanswered for weeks.
6. **Legal pages:** privacy notice (with the enquiry retention period), terms or booking policy (only the owner's wording), cookie notice if any tracking needs consent, and an imprint where the law requires one (Germany, Austria).
7. **Changes to live content** go through `docs/data/content-changes/*.json` and `scripts/apply-content-change.sh` (stage `wp-ship`). Never through hand edits on live.

## Photo steps (details: `references/photo-library.md`)
1. Keep the owner's originals in a backed-up folder outside git.
2. `python3 -I scripts/photos/photos.py review <folder>` → a review CSV: duplicates (perceptual hash), photos already in the library, small photos.
3. **Look at every candidate yourself**, or have a cheaper model do it with the CSV. Decide IMPORT, HOLD-people (no consent yet), HOLD-licence (stock or unknown), or SKIP.
4. Write the manifest (`group,file,key,alt`). Run the import with `--dry-run`, then for real. Paste the printed lines into `photo-library.php`.
5. Templates use `<prefix>_photo( 'key' )`. Use at most two photos per section, and only the LCP image loads eagerly.

## Gate (tick in SITE_STATUS.md)
- [ ] Every public page has owner-approved copy (dates)
- [ ] Real photos imported with alt text; AI images logged in `AI_IMAGES.md`
- [ ] Privacy notice, terms and policies published (owner wording)
- [ ] No `PENDING-` facts and no placeholder text on public pages

## Never
- Fake or imported review counts, star ratings, "as seen in" badges, "12,000+ happy customers" without proof. (We removed a made-up number like that, and made-up review counts on an old site.)
- AI images of a named room, place, product or person, or anything that says "this is what you get".
- Photos of people without their consent. Stock photos without a licence you can show.
- A price or a policy sentence typed into a template or a translation.
