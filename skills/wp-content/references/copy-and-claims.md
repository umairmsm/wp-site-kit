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
- Show the draft on staging, not as a document. People review a real page faster.
- Write down every approval in `DECISIONS.md` (what, who, date). The commit that publishes it names the approval.
