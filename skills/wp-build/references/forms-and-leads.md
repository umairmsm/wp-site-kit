# Forms and leads: never lose one, never leak one

A lead is money. The pipeline in `modules/enquiry.php`, step by step:

| Step | What | Why |
|---|---|---|
| 1 Honeypot | Hidden `website` field; if filled, pretend success and keep nothing | Bots fill every field; real people never see it |
| 2 Rate limit | Per hashed IP per hour: over 5 the lead is **saved but held** (not emailed; flagged in the list); over 30 it is dropped. Private/proxy IPs are not limited. Both numbers are filters | Stops floods without losing real customers behind a shared office or mobile network |
| 3 Nonce | Verified and **recorded** (`_nonce_ok`), not enforced | A page cache can serve a contact page with an expired nonce; a 403 would lose a real customer |
| 4 Validate | Name, valid email, consent box; else a 400 page with a back link. The consent sentence and privacy URL shown are stored with the lead | GDPR needs consent, and you must be able to show what the person agreed to |
| 5 Save | `wp_insert_post` into the private `<prefix>_enquiry` type **before** anything else | If mail or a CRM fails, the lead still exists |
| 6 Hook | `do_action( '<prefix>_enquiry_saved', $id )` | Forward to a CRM or chat app server-side; keys come from `wp-config.php` |
| 7 Email | `wp_mail` to `<PREFIX>_ENQUIRY_TO` with `Reply-To` the customer; `_mail_sent` recorded | The owner answers by replying |
| 8 Retry | Hourly cron re-sends where `_mail_sent = 0` (3 tries; held leads are skipped). Admins see a notice while any lead is unsent | Mail servers have bad hours, and nobody reads logs |
| 9 Purge | Daily cron deletes enquiries older than the retention (default 24 months) | GDPR: keep data only as long as needed; say the same period in the privacy notice |
| 10 Privacy tools | Exporter and eraser registered | Tools > Export/Erase Personal Data finds them by email |

If saving fails, say so (500, "please email us"). Never show "thank you" for a lead you did not store.

## Never
- API keys, form-service endpoints or CRM URLs in HTML or JavaScript. One site had a test key hard-coded as a fallback; anyone could read it.
- A JS-only form that posts to a third-party domain.
- Storing raw IP addresses. A hash is enough for rate limits and abuse checks.

## Mail that arrives
- **From** = `noreply@<your domain>` (`<PREFIX>_MAIL_FROM`), never `wordpress@<host>`.
  On a subdomain host with no SPF, Gmail rejected the default sender (550 5.7.26).
- **Return-Path** aligned with From (`phpmailer_init`), so SPF checks your domain, not the server's. The core does this only when PHP `mail()` sends; an SMTP plugin sets its own sender and is left alone.
- DNS: SPF includes the sending server; DKIM is on (cPanel or the mail provider); add a DMARC record (start with `p=none` and a report address).
- For volume or bad shared-host reputation, send through an SMTP provider. Its credentials go in `wp-config.php`, never in the repo.

## Outside production
The `environment` module rewrites every outgoing email to `<PREFIX>_ENQUIRY_TO` when that constant is set (a test inbox on staging), otherwise to the site admin, and drops Cc and Bcc.
Locally, DDEV's Mailpit catches everything (`ddev launch -m`). On staging, test enquiries
must never reach the business inbox.

## Test it end to end (local)
1. Submit the form on `/contact/`. You land on the thank-you URL.
2. wp-admin > Enquiries: the record exists, with `Form token valid = 1`.
3. Mailpit: one email with Reply-To set to the customer.
4. Submit with the honeypot filled (devtools): no record is created.
5. `ddev wp cron event list`: retry and purge jobs are scheduled.
6. `npm run test:forms` covers steps 1–3 automatically. It never runs against production.
