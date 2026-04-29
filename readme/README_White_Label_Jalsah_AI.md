# White-label Jalsah AI on a partner domain (WordPress backend)

This guide explains how to run the Jalsah AI experience on **another organization’s domain** (white label) while **keeping your WordPress site** as the single backend: users, therapists, bookings, payments, and AI flows all stay on your infrastructure.

It also covers **partner-specific therapist pricing** (overrides) and a **scalable way to register many white-label partners** (doctors / clinics) as you grow.

---

## 1. What you are building

| Layer | Owner | Typical host |
|--------|--------|----------------|
| **Backend** | You (Jalsah) | Your WordPress URL, e.g. `https://jalsah.app` |
| **Frontend SPA** | You build once; per partner you configure branding + env | Partner’s domain, e.g. `https://care.example.com` |
| **Data** | Your WordPress + WooCommerce + plugin tables | Same as today |

The visitor’s browser loads HTML/JS/CSS from **the partner origin**, but API calls go to **your WordPress** (`/api/ai/...` and/or `/wp-json/jalsah-ai/v1/...`).

---

## 2. How the codebase supports this today

### 2.1 CORS and allowed origins

Cross-origin requests from the partner’s site must be explicitly allowed. The plugin reads a list of allowed frontend **origins** (scheme + host + port, no path) from the WordPress option:

- **Option name:** `snks_ai_frontend_urls`
- **Format:** one full base URL per line (e.g. `https://care.example.com`)
- **Used by:** `SNKS_AI_Integration::get_allowed_frontend_origins()` in `functions/ai-integration.php`, which gates `Access-Control-Allow-Origin` for `/api/ai/` and related `admin-ajax.php` CORS handling.

**Operational rule:** every new white-label deployment URL must be added here (production **and** staging, e.g. `https://staging.care.example.com`), then saved from the AI admin UI that persists this field (see `functions/admin/ai-admin-enhanced.php`).

> **Note:** If the SPA calls **WordPress core REST** under `/wp-json/jalsah-ai/v1/...`, verify in the browser that preflight and responses include the correct CORS headers for that path. The plugin’s primary CORS hooks target `/api/ai/` and `admin-ajax.php`. Extend CORS consistently for any additional URL patterns the frontend uses.

### 2.2 API surface

- **Rewrite API:** `https://YOUR-WORDPRESS/api/ai/...` (registered in `functions/ai-integration.php`).
- **REST namespace:** `jalsah-ai/v1` (same file + other modules).

Existing integration docs: `readme/README_JalsahAI_Integration.md`, `jalsah-ai-frontend/README.md`.

### 2.3 Frontend configuration

The Vue app (`jalsah-ai-frontend`) uses environment variables for the API base (see `jalsah-ai-frontend/README.md` and `src/services/api.js`).

For a **separate domain** white label:

- Build the SPA with env pointing **API traffic** to your WordPress host (absolute origin or proxy strategy your team agrees on).
- Keep `withCredentials: true` in mind: the browser will only send cookies to the **API origin** (your WordPress domain), not to the partner’s static host. Auth already uses a **Bearer token** in `localStorage` (`jalsah_token`) in many flows—confirm all critical paths work without relying on cross-site cookies.

---

## 3. End-to-end setup checklist (first partner)

### 3.1 On your WordPress (Jalsah)

1. **Add the partner frontend origin** to `snks_ai_frontend_urls` (one URL per line, no trailing slash inconsistency—match exactly what the browser sends in `Origin`, including `https` vs `http`).
2. **Flush permalinks** if you change anything affecting rewrites (Settings → Permalinks → Save).
3. **SSL:** your API must be HTTPS in production.
4. **Decide partner identity** for pricing and analytics (see §5): at minimum define a stable `partner_id` (slug) you will send from the frontend and honor on the server.

### 3.2 On the partner’s hosting (or your CDN for them)

1. Deploy the **built** SPA (`npm run build` output) to their domain (Netlify, Vercel, S3+CloudFront, their VPS, etc.).
2. Set env vars for that build so API requests hit **your** WordPress, not their bare domain (unless you put a reverse proxy on their domain that forwards `/api` to you—advanced).
3. **Branding:** logo, colors, app name, legal pages (privacy/terms) on **their** domain; link to your processor notice if required by your DPA.

### 3.3 Smoke tests

- Open the app from the **partner origin**; confirm therapist list, diagnosis flow, login, cart, checkout.
- Watch **Network** tab: preflight `OPTIONS` and main requests return `Access-Control-Allow-Origin` matching the partner origin when listed in `snks_ai_frontend_urls`.
- Test payment redirect return URLs if gateways whitelist domains—add partner domains to payment provider allowlists if needed.

---

## 4. Partner-specific therapist price overrides

Today, therapist session prices are driven primarily from **user meta** on each therapist (doctor) user, for example:

- `45_minutes_pricing`, `45_minutes_pricing_others`
- `60_minutes_pricing`, `60_minutes_pricing_others`
- `90_minutes_pricing`, `90_minutes_pricing_others`
- Demo doctors may use simpler keys such as `price_45_min` (see `functions/ai-integration.php` and `functions/helpers/coupons.php`).

Those values represent **your platform’s default** price for that therapist.

For white label, the business requirement is: **same therapist, different public price on partner A vs partner B vs main Jalsah site**.

That is **not** a single global user-meta field; you need a **layered pricing model**:

### 4.1 Recommended model: “effective price” resolution

Define resolution order (conceptually):

1. **Partner override** — if a price exists for `(partner_id, therapist_id, period, country_or_tier)`, use it.
2. **Else therapist default** — current meta / `snks_get_ai_therapist_price` / `get_therapist_ai_price` behavior.

Implementation touchpoints (for your developers—align with actual call sites):

- AI therapist detail and availability responses in `functions/ai-integration.php`.
- Cart line price rebuild in `functions/helpers/coupons.php` (and any AI cart endpoints).
- Checkout / WooCommerce line item creation so the **order stores the price actually charged**.

Keep **audit fields** on orders: e.g. `from_jalsah_ai`, and extend with `jalsah_partner_id` so support and settlements know which white label sold the session.

### 4.2 How to store overrides (suggested)

Pick one (or combine):

| Approach | Pros | Cons |
|----------|------|------|
| **Option or custom table** `partner_pricing` keyed by partner + therapist + period | Easy admin tooling, no duplication per therapist account | More rows as partners grow |
| **JSON blob** per partner in an option | Quick for few partners | Harder to query/report |
| **Percentage markup** per partner | Tiny config | Less control per therapist |

Country-aware overrides should mirror your existing country vs “others” structure so you do not regress international pricing.

### 4.3 Passing `partner_id` from the white-label app

The SPA should send a stable identifier on **every** API call once you implement server-side support, for example:

- Header: `X-Jalsah-Partner: care-example` (preferred—does not pollute URLs), **and/or**
- Query param: `partner=care-example` (easier to debug; must still be validated server-side).

The server must:

- Validate `partner_id` against a **registered** partner (secret key optional; see §6).
- Apply override pricing only for that partner.
- Reject unknown `partner_id` values (treat as main site or 403, depending on product policy).

Until code exists, document the **intended** contract in your internal ticket and keep one partner on a **forked build** with hardcoded display prices only as a temporary measure—not recommended for checkout integrity.

---

## 5. Registering white-label partners at scale (future-friendly)

You asked for an **easy way to register** each doctor’s/clinic’s white label. Operationally and in software, treat each deployment as a **Partner record**.

### 5.1 Minimum fields for a partner record

| Field | Purpose |
|--------|---------|
| `partner_id` | Stable slug (`acme-clinic`) |
| `display_name` | Internal admin label |
| `allowed_origins[]` | Must stay in sync with `snks_ai_frontend_urls` entries for that partner |
| `status` | `active` / `suspended` |
| `branding` (optional JSON) | Logo URL, primary color, legal name for emails |
| `price_overrides` or link to pricing table | §4 |
| `api_secret` (optional) | HMAC or static header to reduce abuse of public endpoints |
| `notes` | Contract, go-live date, billing model |

### 5.2 Keeping CORS in sync

Whenever you **approve** a new origin:

1. Insert/update the partner record.
2. Append the origin string to `snks_ai_frontend_urls` (or automate: on partner save, merge into the option—recommended long term to avoid manual copy-paste errors).

The validation logic today is an **exact string match** on `Origin` (`is_origin_allowed`), so `https://www.partner.com` and `https://partner.com` are **two different origins**—register both if both are used.

### 5.3 Registration workflow (recommended)

1. **Intake form** (internal or later self-service): partner submits domain, branding assets, legal contact.
2. **Technical review:** SSL, DNS, who deploys the SPA build.
3. **You create** partner record + origins + optional secret.
4. **You issue** a build configuration (env file template) for their CI or yours.
5. **Go-live checklist** (§3.3) + payment allowlists.

### 5.4 Future: self-service “doctor portal”

A small admin REST module could:

- `POST /wp-json/jalsah-ai/v1/partners` (super-admin only) — create partner, return `partner_id` + rotate secret.
- `GET` / `PATCH` for status and branding.

That is **specification** until implemented; the table above is enough for a first internal CRM (even a spreadsheet) before you codify it.

---

## 6. Security and abuse prevention

Public routes under `jalsah-ai/v1` often use permissive `permission_callback` patterns for the mobile/web app. For multi-partner production:

- **Require** a valid `partner_id` (and optionally secret) for white-label–only features and for applying price overrides.
- **Rate limit** by partner + IP at the edge (Cloudflare, nginx) or in WordPress.
- **Never** trust the client for final price: always recompute on the server using the same rules as checkout.
- **Rotate** partner secrets if a build leaks.

---

## 7. Legal and commercial (non-technical reminders)

- Data processing agreement: who is controller vs processor for patient data on white-label domains.
- Refund and chargeback policy per partner if they use **your** merchant account.
- If they use **their** payment account, engineering for split payouts is a separate project.

---

## 8. Quick reference — files and options

| Item | Location / name |
|------|------------------|
| Allowed frontend origins | Option `snks_ai_frontend_urls`; logic in `functions/ai-integration.php` |
| AI admin saving frontend URLs | `functions/admin/ai-admin-enhanced.php` |
| REST + rewrite AI routes | `functions/ai-integration.php` |
| Therapist pricing helpers | `functions/helpers/pricing.php`, AI integration price methods |
| SPA API client | `jalsah-ai-frontend/src/services/api.js` |
| SPA env documentation | `jalsah-ai-frontend/README.md` |

---

## 9. Summary

1. **Host the SPA** on the partner domain; **point API** to your WordPress.
2. **Register each origin** in `snks_ai_frontend_urls` so CORS allows the browser to talk to your API.
3. **Introduce a partner layer** (`partner_id` + server-side registry) for branding, auditing, and **price overrides** resolved *after* therapist defaults.
4. **Centralize partner onboarding** in a repeatable checklist and, when ready, a small admin or REST module so adding “another doctor’s white label” is routine instead of ad hoc.

For questions about the existing AI ↔ WordPress contract, start with `readme/README_JalsahAI_Integration.md`.
