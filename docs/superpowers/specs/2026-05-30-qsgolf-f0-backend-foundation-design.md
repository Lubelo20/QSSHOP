# QSGOLF Platform — F0: Backend Foundation Design

**Date:** 2026-05-30
**Status:** Approved (design); pending implementation plan
**Author:** Brainstormed with Claude Code

---

## 1. Context

QSGOLF currently ships as a **static front-end only**: hand-written HTML pages, vanilla-JS
ES modules, a `localStorage` cart, and a simulated checkout. There is no server, database,
authentication, file storage, or payments — nothing persists beyond the browser.

The wider business goal (see "Roadmap" below) is a full production e-commerce platform that
sells standard QSGOLF products **and** offers a custom apparel designer for golf clubs and
corporates. That full scope is many independent subsystems and cannot be one spec.

### Decisions taken during brainstorming
- **Intent:** Real production platform (real customers, real payments, operated long-term).
- **Foundation approach:** Keep the existing static front-end; add a custom backend + API behind it.
- **Stack:** PHP + MySQL on **Laravel** (gives auth scaffolding, Filament admin, queues, mail,
  PDF, file storage out of the box; strong PayFast/Ozow ecosystem in South Africa).
- **Build order:** F0 foundation → Standard store (S1–S4) → Custom apparel (D1–D4) → Operations.
- **F0 integration model:** **Approach A** — Laravel serves the existing static files from
  `public/`; the front-end fetches catalog data from a JSON API. Same-origin, so no CORS.

This document specifies **F0 only** — the keystone every later slice attaches to.

---

## 2. Roadmap (context, not part of F0)

Each sub-project below gets its own spec → plan → implementation cycle. F0 is first and
non-negotiably blocking. Listed here so F0's boundaries are clear.

**Foundation**
- **F0 · Backend foundation** *(this spec)* — Laravel + MySQL, serve static front-end,
  move catalog from `products.js` into the DB behind a JSON API.

**Standard store (Option 1 — revenue first)**
- S1 · Accounts & customer portal
- S2 · Server-side cart, checkout & orders
- S3 · Payments (PayFast, Ozow, EFT)
- S4 · Admin dashboard (Filament): products, categories, inventory, orders, customers, reporting

**Custom apparel (Option 2 — differentiator)**
- D1 · Custom product designer (product → colour → upload logos → placement → text → live preview)
- D2 · Pricing engine (base + branding methods + quantity tiers)
- D3 · Quote system (request → PDF → email → convert to order)
- D4 · Golf-club accounts (club profile, branding library, saved templates, multi-user)

**Operations**
- W1 · Manufacturing workflow + custom-order status tracking
- R1 · Reporting & analytics

---

## 3. F0 Architecture

A single **Laravel application is the entire project**. The existing static site moves into
Laravel's `public/` directory and is served as-is — same HTML/CSS/JS, same URLs
(`/index.html`, `/drinkware.html`, `/product.html`, …). Laravel adds a JSON API under `/api/*`.

Because the front-end and the API are served from the **same origin**, there is no CORS to
configure. MySQL holds the catalog. Pages fetch catalog data from the API instead of reading
a hardcoded array.

```
Browser ──/drinkware.html──▶ Laravel (serves static file from public/)
Browser ──/api/products────▶ Laravel controller ──▶ MySQL
```

---

## 4. Data Model (MySQL)

### `categories`
Ports the 5 entries in the current `CATEGORIES` array.

| Column       | Type                | Notes                          |
|--------------|---------------------|--------------------------------|
| id           | bigint, PK          |                                |
| slug         | string, unique      | e.g. `apparel`, `drinkware`    |
| name         | string              | e.g. `Apparel`                 |
| emoji        | string              |                                |
| description  | text                |                                |
| sort_order   | unsignedInteger     | controls display order         |
| timestamps   | created/updated     |                                |

### `products`
Ports the 14 products in the current `PRODUCTS` array.

| Column       | Type                  | Notes                                                |
|--------------|-----------------------|------------------------------------------------------|
| id           | bigint, PK            |                                                      |
| sku          | string, unique        | e.g. `QS-APP-001` (also the public id)               |
| name         | string                |                                                      |
| category_id  | bigint, FK→categories |                                                      |
| price        | unsignedInteger       | whole Rand (no cents currently in use)               |
| orig_price   | unsignedInteger       | whole Rand                                           |
| emoji        | string                |                                                      |
| badge        | string, nullable      | e.g. `Popular`, `Sale`, `New`, `null`                |
| stock        | unsignedInteger       |                                                      |
| image        | string                | image URL (Unsplash URLs retained for F0)            |
| description  | text                  |                                                      |
| features     | json                  | array of strings                                     |
| variants     | json, nullable        | `{"sizes":[...]}` or `{"colours":[...]}` or `null`   |
| sort_order   | unsignedInteger       |                                                      |
| timestamps   | created/updated       |                                                      |

**Decisions (approved):**
- Prices stored as **whole Rand integers** — matches current data; migration to integer cents
  is possible later if fractional pricing is needed.
- `features` and `variants` stored as **JSON columns**, not separate tables — matches the
  current shape and the read-only needs of F0. Normalisation can come with S4 admin if needed.

### Seeding
A Laravel seeder ports all current data verbatim from `js/products.js` (`PRODUCTS` and
`CATEGORIES`) so the live catalog is byte-for-byte equivalent to today.

---

## 5. API (read-only in F0)

| Method & path             | Returns                                  |
|---------------------------|------------------------------------------|
| `GET /api/products`       | array of all products                    |
| `GET /api/products/{sku}` | one product; `404` if not found          |
| `GET /api/categories`     | array of all categories (by sort_order)  |

**Critical contract — JSON shape must match the current object exactly:**

```json
{
  "id": "QS-APP-001",
  "sku": "QS-APP-001",
  "name": "QS Golf Shirt — Dark Green",
  "category": "Apparel",
  "slug": "apparel",
  "price": 580,
  "origPrice": 750,
  "emoji": "👕",
  "badge": "Popular",
  "stock": 18,
  "image": "https://images.unsplash.com/...",
  "variants": { "sizes": ["S","M","L","XL","XXL"] },
  "features": ["Moisture wicking fabric", "..."],
  "description": "..."
}
```

A Laravel API Resource performs the mapping: `id`/`sku` ← `sku`, `category` ← `category.name`,
`slug` ← `category.slug`, `origPrice` ← `orig_price`. Keeping this shape identical is what
makes the front-end changes minimal.

Filtering and sorting remain **client-side** in F0 (the pages already do this), so
`/api/products` simply returns the full list. Query-param filtering can be added later without
breaking callers.

---

## 6. Front-end Changes (Approach A)

Eight files currently import the catalog synchronously (`index.html`, `apparel.html`,
`drinkware.html`, `gift-sets.html`, `golf-accessories.html`, `stationery.html`,
`product.html`, and `js/cart.js`). F0 converts the data source from a hardcoded array to the
API while preserving every function name and the page logic.

- **`js/products.js`** — remove the hardcoded `PRODUCTS` array. Add
  `async loadProducts()` that fetches `/api/products` once and caches the result. Keep
  `getProductById`, `getProductsByCategory`, `getFeatured`, and `CATEGORIES` reading from that
  cache (load `CATEGORIES` from `/api/categories` or keep inline — implementation detail for
  the plan). Same exported names, so callers barely change.
- **Page scripts** (`index`, 5 category pages, `product.html`) — add a single
  `await loadProducts()` before the first `render()`. Existing filter/sort/render logic is
  untouched.
- **`js/cart.js`** — logic unchanged; it reads the cache. Ensure `cart.html` and
  `checkout.html` call `loadProducts()` on load so the cache is warm before cart items resolve.
- **Untouched in F0:** localStorage cart, simulated checkout/order-confirmed flow,
  `css/styles.css`, `components/*`, `js/includes.js`, `js/utils.js` logic.

---

## 7. Testing

**Backend (automated — Laravel feature tests):**
- `GET /api/products` returns 200 and 14 products with the exact JSON shape.
- `GET /api/categories` returns 200 and 5 categories in `sort_order`.
- `GET /api/products/{sku}` returns the right product; unknown SKU returns 404.
- Seeded counts match (14 products / 5 categories).

**Front-end (manual smoke checklist):**
- Every category page and the shop-all page render their products from the API.
- Product detail page loads by `?id=SKU`, shows variants, related products, tabs.
- Add-to-cart still works and the cart badge updates.
- Cart and checkout pages resolve product data correctly from the warmed cache.

---

## 8. Setup / Housekeeping

### 8.0 Preflight — environment setup (must pass before any code)

F0 implementation cannot start until the local toolchain is present and verified. The plan's
first step is a preflight gate that checks each tool, reports versions, and stops with clear
install instructions if anything is missing — so implementation never stalls midway.

**Required toolchain (macOS / darwin):**

| Tool      | Min version | Verify command         | Install (macOS, Homebrew)                     |
|-----------|-------------|------------------------|-----------------------------------------------|
| PHP       | 8.2+        | `php -v`               | `brew install php`                            |
| Composer  | 2.x         | `composer --version`   | `brew install composer`                       |
| MySQL     | 8.0+        | `mysql --version`      | `brew install mysql` then `brew services start mysql` |
| Laravel   | 11.x        | (via Composer)         | installed by `composer create-project`        |

**Preflight gate procedure:**
1. Run each verify command; record versions.
2. If any tool is missing or below the minimum, **halt** and present the exact install
   commands above; do not scaffold until the user confirms tools are installed.
3. Confirm a MySQL server is reachable and create an empty `qsgolf` database
   (`CREATE DATABASE qsgolf;`).
4. Confirm a DB user/credentials are available for `.env`.

This step is interactive where needed: some installs (e.g. `brew install`, starting MySQL,
setting a MySQL root password) are run by the user in their own shell. The plan will flag which
commands the user runs versus which the implementation runs.

### 8.1 Project setup (after preflight passes)

- Fresh Laravel install; existing static site relocated into `public/`.
- `.env` configured for the `qsgolf` MySQL database; run migrations + seeder.
- `php artisan serve` for local development.
- **`git init`** this folder (not currently a git repo) to commit this spec and track all work.
  *(Done during brainstorming — repo initialized, baseline committed.)*

---

## 9. Out of Scope for F0

Deferred to later slices so F0 stays focused on making the catalog real and API-backed:
authentication/accounts, server-side cart, real checkout & payments, admin UI, file uploads,
the custom apparel designer, and quotes.

---

## 10. Success Criteria

F0 is complete when:
1. The catalog is served from MySQL via `/api/products`, `/api/products/{sku}`, `/api/categories`.
2. Every existing page renders identically to today, sourcing data from the API.
3. Backend feature tests pass; the manual front-end smoke checklist passes.
4. The project runs locally with `php artisan serve`, and the spec + code are committed to git.
