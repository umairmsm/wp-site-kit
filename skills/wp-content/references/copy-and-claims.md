# Copy and claims

## Where truth lives
| Kind of fact | Source | Where it lives on the site |
|---|---|---|
| Prices, durations, inclusions | owner (data file or BRIEF) | post meta, entered once, shown by core functions |
| Policies (deposit, cancellation, fees, refunds) | owner's own wording | one core function (`<prefix>_policy( $key )`) + `.mo` |
| Safety, legal, visas, health | official sources, with the date checked | page text, with "checked on <date>" in the source notes |
| Reviews, ratings | a public profile you can link to | quoted word for word, with name and date as published, and the guest's agreement |
| Team, guides, staff | owner | about page; real names and roles only |

Keep a flags file per content type (`docs/data/<type>/_SUMMARY.md`): each fact still unconfirmed,
who must confirm it, and since when. Review it before every publish.

## Claims audit (before customer-facing text ships)
For every sentence that states a fact:
1. Find its source (owner note, data file, policy function, official page). If there is none, cut it or flag it.
2. Numbers: same number as the source, same unit, same currency.
3. Promises ("guaranteed", "always", "free cancellation"): word for word as the owner's policy says, or cut.
4. Comparisons ("best", "#1", "cheapest"): cut, unless there is published proof.
5. Translations: no fact, place, meal, time or promise that isn't in the original.

## Claims: rules the source site learned the hard way
- **Run the claims audit before opening the PR**, not after review. Once, a page went to review claiming a meeting point, a registration and "guaranteed" sightings that nobody could back up.
- **When unsure, soften to what is true and point to the written quote:** "meeting point sent once your booking is confirmed", "what's included is in your written quote". Then add a test that bans the removed claim, so it can't come back.
- **No price on the page until the operator confirms it in writing.** Until then: "Price on request", plus a test that fails on any currency figure on that page.
- **Dates:** quote the official schedule, add "subject to change", and never round or guess.
- **Quote the partner's saved answer, not your summary of it.** A safety line was written from a memory note ("we change or postpone it"). The partner's real answer only said "postpone", and the wrong line stayed live for 3 days in 4 languages. Before writing or restoring a claim, open the saved answer and match its words.
- **Keep "verified" and "not yet confirmed" apart.** Note where each fact came from (official page, or a partner message and its date), on the day it arrives.
- **Re-read the partner's answers for contradictions** (a route that "ends in city A" and "ends in city B"). Ask a closed question again; never pick one yourself.
- **Every contact button goes to the right person.** On a page for a second country, the WhatsApp button rang the first country's operator. Give each region its own number, with a test that checks it.
- **A banned word is lifted only by the owner's written OK**, recorded with its date, and the test that bans it changes in the same PR.
- **Rebuild derived copies after the live content changes, not before.** Search indexes, chat knowledge files and PDFs built from the site went out with a claim that had just been removed. Rebuild after every live content change, with a test that bans the removed claim.

## Words to avoid unless the owner has proof
best · #1 · leading · luxury · authentic · official · guaranteed · world-class · award-winning ·
unique · exclusive · certified · licensed (unless you show the licence) · "100%" anything

## Writing style that worked
- Plain sentences, concrete details (what, where, how long, what is included).
- Write for the visitor's question, not for keywords. Use their words (from emails and search queries).
- One idea per paragraph. Headings that say what follows.
- AI text gets an extra pass for filler ("nestled", "vibrant tapestry", "embark on a journey"). Delete it.

## Working with the owner
- Send a short message with one question that can be answered with yes or no: "Is the half-day price still 60 EUR per person? Yes or no."
- Before sending the owner or partner to post somewhere (a forum, a subreddit), check who runs it. A community run by a competitor removed the partner's reply.
- Show the draft on staging, not as a document. People review a real page faster.
- Write down every approval in `DECISIONS.md` (what, who, date). The commit that publishes it names the approval.
