# QSSHOP — Deploying to Railway (Laravel + MySQL)

This app is **Laravel (PHP) + MySQL**. It must run on a host with a PHP runtime and a MySQL
database. **It will not work on Vercel/Netlify static hosting** — those can't run the PHP API
(`/api/products`, `/api/categories`) that the storefront fetches at runtime.

These steps deploy the real app on **Railway**. You do the dashboard clicks (account/billing is
yours); the repo already contains the config to make it work (`railway.json`).

---

## What's already wired in the repo

- **`railway.json`** — on every deploy Railway runs `php artisan migrate --force --seed`
  *before* the new version goes live (creates tables + seeds the 14 products / 5 categories;
  the seeders are idempotent, so re-running is safe), health-checks `/up`, and restarts on
  failure.
- Railway auto-detects Laravel and serves `public/` via php-fpm/Caddy — no web-server config
  needed.
- The Vite config was trimmed (removed the network-dependent font fetch) so the asset build
  can't fail the deploy. (The storefront uses its own `public/css` + `public/js`; it does not
  depend on the Vite build.)

---

## Step-by-step

### 1. Create the project from GitHub
1. Go to **https://railway.com** → sign in with GitHub.
2. **New Project** → **Deploy from GitHub repo** → pick **`Lubelo20/QSSHOP`**.
3. Railway detects Laravel and starts a first build. It will likely **fail or crash-loop until
   the database + env vars below are set** — that's expected. Finish steps 2–4, then redeploy.

### 2. Add a MySQL database
1. In the project canvas: **New** → **Database** → **Add MySQL**.
2. This creates a `MySQL` service that exposes `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`,
   `MYSQLUSER`, `MYSQLPASSWORD`.

### 3. Set environment variables on the **app** service
Open the **QSSHOP app** service → **Variables** → add these (use **Raw Editor** to paste all at
once). The `${{MySQL.*}}` values are *reference variables* — Railway fills them from the MySQL
service automatically. If you named the DB service something other than `MySQL`, change the
prefix to match.

```
APP_NAME=QSSHOP
APP_ENV=production
APP_KEY=base64:4akyZd22WW640GNDWZklfyUC4MdV2ih/9oY0XiRFT3c=
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
ASSET_URL=${{RAILWAY_PUBLIC_DOMAIN}}

LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Notes:
- **`APP_KEY`** above is a freshly generated Laravel key for this app. To rotate it later, run
  `php artisan key:generate --show` locally and replace the value. The app will not boot without
  a valid `APP_KEY`.
- **`LOG_CHANNEL=stderr`** is important: Railway's filesystem is ephemeral, so file logs are lost.
  stderr logs show up in Railway's deploy logs.
- `SESSION/CACHE/QUEUE = database` use tables created by the default migrations (the pre-deploy
  `migrate` step creates them). No Redis needed for F0.

### 4. Expose a public URL
1. App service → **Settings** → **Networking** → **Generate Domain** (gives a
   `*.up.railway.app` URL). This is what `${{RAILWAY_PUBLIC_DOMAIN}}` resolves to.

### 5. Redeploy
1. **Deployments** → **Deploy** (or push any commit). On boot Railway runs the
   `preDeployCommand` (`php artisan migrate --force --seed`), then serves the app.
2. Watch the deploy logs. Success looks like migrations running, then the server starting.

### 6. Verify it's live
Open these on your generated domain:
- `https://<your-domain>/` → storefront with **14 products** rendering.
- `https://<your-domain>/api/products` → bare JSON array of 14 products.
- `https://<your-domain>/api/categories` → 5 categories.
- `https://<your-domain>/up` → Laravel health page (used by Railway's health check).

If the grid is empty but `/api/products` returns data, hard-refresh (cached old JS).

---

## Troubleshooting

| Symptom | Cause / Fix |
|---|---|
| Build crash-loops on first deploy | DB/env not set yet — complete steps 2–4, then redeploy. |
| `SQLSTATE[HY000] [2002] Connection refused` | DB_* vars wrong/missing, or DB service not linked. Re-check the `${{MySQL.*}}` references and that both services are in the same project. |
| 500 + "No application encryption key" | `APP_KEY` not set. Add it (step 3). |
| `/` shows empty product grid | `/api/products` not returning data → check the deploy log for migrate/seed errors. |
| Migrations didn't run | Confirm `railway.json` `preDeployCommand` is present; check deploy logs for the migrate step. |

---

## Cost & later moves
- Railway is usage-based with a trial credit; an idle small Laravel app + MySQL is inexpensive.
- For production launch to South African customers, consider moving to a **SA-based PHP/MySQL
  host** (Afrihost/HostAfrica cPanel) or **DigitalOcean Frankfurt** for lower latency and ZAR
  billing. It's portable: it's just this Laravel repo + a MySQL dump.
- **Laravel Cloud** (cloud.laravel.com) is the most Laravel-native alternative if you'd rather
  have zero server/ops management as the platform grows (S1–S4, queues for the custom designer).
