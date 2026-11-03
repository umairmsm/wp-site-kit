# Discovery brief: questions and BRIEF.md layout

Ask in plain words, one group at a time. Write the answers in `docs/BRIEF.md` under the same
headings. Give every fact a source: (owner), (doc: name), (crawl: URL), or `PENDING-<who>`.

## 1. Business
- What do you sell, to whom, and in which countries?
- How do customers buy today (enquiry, booking, cart, phone, chat app)? How do they pay?
- What are the top three jobs for the website (for example: enquiries, bookings, trust, search traffic)?
- What makes you different, in your own words? Which competitors do you like or dislike, and why?
- Is there a busy season or a hard launch date?

## 2. Content truth
- Who supplies the facts: prices, what is included, policies, safety information?
- Who approves customer-facing copy? Who approves design?
- Which photos are real and yours? Who took them, and can we use them? Are there people in them, and did those people agree?
- Do you have reviews you can prove (a public profile with a link)? Never invent or import counts.
- Licences, registrations or memberships the site must show.
- Words or promises the owner never wants (for example "best", "guaranteed", "luxury").

## 3. Legal and trust
- Visitors from the EU or UK? Then you need GDPR basics: a privacy notice, consent before tracking, self-hosted fonts, and a retention period for leads.
- Terms, booking or cancellation policy, refund rules: who wrote them, and are they final?
- Any regulated claims (health, safety, finance, travel advisories)?
- Accessibility target (default: WCAG 2.1 AA).

## 4. Markets and languages
- Which languages at launch, and which later?
- Who is the native-speaker reviewer for each language? Never publish machine translation without one.

## 5. Leads and operations
- Where should leads go (email, chat app, CRM)? Who answers them, and how fast?
- How long may we keep a lead (for example 12 months), and who can see them?

## 6. Accounts and tech
- Domain registrar and who owns the account.
- DNS host (Cloudflare recommended) and who controls it.
- Mail: where does company email live? Record the MX records **before** any DNS change.
- Current host; SSH and WP-CLI access; staging possibility.
- Analytics, Search Console, business profiles, payment provider, CDN.

## 7. Existing site (if any)
- Every URL from the sitemaps (save them to `docs/data/legacy/urls.txt`).
- Forms, booking or payment steps, and where their data goes.
- Blog posts and pages with search traffic (Search Console: top pages, last 12 months).
- Anything that must not break on launch day.
- Old review counts, ratings or "as seen in" badges: mark each one proven or unproven.

## BRIEF.md layout
```markdown
# BRIEF — <Site name>
Approved by: <owner> on <date>   (until then: DRAFT)

## Business            (facts with sources)
## Customers and markets
## Jobs of the website (top 3, measurable)
## Content truth       (who supplies what; PENDING list)
## Legal and trust
## Languages
## Leads and operations
## Accounts            (names only, see CREDENTIALS_INVENTORY.md)
## Existing site       (inventory summary + link to docs/data/legacy/)
## Open questions      (each with an owner and a date)
```
