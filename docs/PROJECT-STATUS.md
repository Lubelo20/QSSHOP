# QSGOLF / QSSHOP — Project Status & Resume Guide

_Last updated: 2026-05-30_

A pick-up-where-we-left-off snapshot. For the full design and task detail, see the spec and
plan under `docs/superpowers/`.

---

## What this project is
A South-African golf-merchandise e-commerce platform ("QS Golf Coordinators" / QSSHOP). Goal:
sell standard QSGOLF products **and** offer a custom apparel designer for golf clubs/corporates.

## Key decisions locked in
- **Real production platform** (real customers, real payments), built to scale.
- **Keep the existing static front-end** (hand-written HTML/CSS/vanilla-JS in `public/`) and add
  a backend behind it.
- **Stack: Laravel (PHP 8.3+) + MySQL.** Laravel v13 installed.
- **Build order:** F0 foundation → Standard store (S1–S4) → Custom apparel (D1–D4) → Operations.

## Roadmap
- **F0 · Backend foundation** — ✅ **DONE** (catalog in MySQL behind a read-only JSON API; static
  storefront now sources data from the API).
- **S1** Accounts & customer portal · **S2** Server-side cart/checkout/orders · **S3** Payments
  (PayFast/Ozow/EFT) · **S4** Admin dashboard (Filament).
- **D1** Custom product designer · **D2** Pricing engine · **D3** Quote system · **D4** Golf-club
  accounts.
- **W1** Manufacturing workflow + status tracking · **R1** Reporting & analytics.

---

## Where we are right now (end of 2026-05-30)
- **F0 is complete and reviewed.** 11 tasks built via TDD, two-stage reviewed, final review =
  READY TO MERGE. **10 tests / 36 assertions passing** (`php artisan test`).
- Code is on GitHub: **https://github.com/Lubelo20/QSSHOP**, branch **`main`** (tip `addf754`).
  Working tree clean, pushed.
- **API:** `GET /api/products`, `GET /api/products/{sku}`, `GET /api/categories` (bare JSON,
  matches the legacy front-end shape exactly). `/` serves the storefront.

## Deployment status
- **Vercel (static demo):** `vercel.json` + `public/api/*.json` snapshots make the storefront
  render on Vercel as a **frozen static demo** (no DB/admin/accounts). Pushing `addf754` should
  have produced a green deploy at the `qsshop-*.vercel.app` domains. _Verify the build went green._
- **Railway (real dynamic app):** repo is **prepared but not yet deployed.** `railway.json` runs
  `php artisan migrate --force --seed` pre-deploy and health-checks `/up`. The Railway CLI is
  installed locally (v4.65.0). **Blocker:** needs a one-time interactive `railway login` (browser
  OAuth, billed to the owner) before the deploy can run. Full steps: `docs/DEPLOYMENT.md`.

---

## To resume during the week

**Run F0 locally:**
```bash
# one-time: cp .env.example .env && php artisan key:generate; set DB_* for the qsgolf MySQL DB
composer install
php artisan migrate --seed
php artisan serve        # http://127.0.0.1:8000
php artisan test         # expect 10 passing
```
(Local MySQL `qsgolf` DB, root/no-password, was used in dev. APP_KEY for Railway is in
`docs/DEPLOYMENT.md`.)

**Pick one of these as the next move:**
1. **Stand up the real backend on Railway** — you run `railway login`, then I drive
   init → add MySQL → set env vars → deploy → verify. (~10 min.)
2. **Start S1 (Accounts & customer portal)** — brainstorm → spec → plan → build, same flow as F0.
   Laravel Sanctum for auth; wires into the existing `my-account.html`.
3. **Confirm/clean up the Vercel static demo** (e.g. replace the stock `README.md` with real
   QSSHOP setup docs).

## Known follow-ups / tech debt (deferred from F0, safe to leave)
- Front-end has no automated test runner (F0 used manual smoke + backend tests).
- `resources/views/welcome.blade.php` and the stock `tests/*/ExampleTest.php` are unused Laravel
  scaffolding — clean up during S1.
- API has no HTTP caching / no filtering query params yet (client-side filtering for now).
- Prices stored as whole-Rand integers; `features`/`variants` as JSON columns (both spec-approved
  for F0; revisit at S3/S4).
- Vercel catalog snapshot (`public/api/*.json`) is frozen — regenerate it from the API if the
  catalog changes, or rely on Railway for live data.
- Consider `preventLazyLoading()` + trusted-proxy config when the app goes to a real host.
