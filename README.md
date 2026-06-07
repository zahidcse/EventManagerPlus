# Event Manager Free

Open-source event ticketing and registration built with **Laravel 12** and **PHP 8.2+**. Create events, sell tickets with **Stripe**, and manage bookings from a modern admin dashboard.

**Need the full product?** Unlock multiple themes, seat plans, recurring events, PDF ticket settings, attendee forms, and extra payment gateways:

**[Purchase Event Manager Plus →](https://lucrativeit.com/products/event-manager-plus)**

---

## Free vs premium

| Feature | Free | [Premium](https://lucrativeit.com/products/event-manager-plus) |
|--------|:----:|:-------:|
| Public site theme | Default & Classic | Classic Light and more |
| Event schedule | Single event (preview recurring/custom) | Recurring & custom dates |
| Early bird pricing | Preview only | Per-ticket discounted windows |
| Additional services | Preview only | Sell add-ons at checkout |
| Seat plans | Preview only | Full seat layout & booking |
| PDF ticket settings | Preview only | Per-event customization |
| Attendee form settings | Preview only | Per-ticket fields |
| Online payments | Stripe | Stripe, PayPal, Razorpay, SSLCommerz |
| Offline payments | Preview only | Cash & bank transfer |

Premium-only settings stay visible in the admin panel with an **“Available in premium version”** badge.

---

## Requirements

| Requirement | Version / notes |
|-------------|-----------------|
| PHP | 8.2 or higher |
| Composer | 2.x |
| Node.js & npm | 18+ (to build frontend assets) |
| Database | MySQL / MariaDB (recommended for production) or SQLite (local testing) |
| Web server | Apache or Nginx with `mod_rewrite` / try-files, **or** `php artisan serve` for local dev |

### Required PHP extensions

`ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, and **`pdo_mysql`** (when using MySQL/MariaDB).

### Writable directories

- `storage/` (and subfolders)
- `bootstrap/cache/`
- `.env` (or ability to create it from `.env.example`)

---

## Installation

### Install using the web installer (recommended)

The built-in wizard at **`/install`** checks your server, writes `.env`, runs migrations, seeds default data, and creates your admin account. Use this for local (Laragon) and production setups.

#### Get the code

```bash
git clone https://github.com/your-org/EventManagerFree.git
cd EventManagerFree
```

Or download and extract the ZIP into your web root (e.g. `D:\laragon\www\EventManagerFree` on Laragon).

#### Before you open `/install`

Do these once from the project root:

```bash
composer install
npm install
npm run build
```

| Step | Why |
|------|-----|
| `composer install` | PHP dependencies (required for the app to run) |
| `npm install` && `npm run build` | Compiles admin/public CSS and JS |

Also make sure:

1. **Document root** points to the **`public/`** folder (not the project root). On Laragon, set the vhost root to `…/EventManagerFree/public`.
2. **`storage/`** and **`bootstrap/cache/`** are writable by the web server.
3. **MySQL or MariaDB** is running and you have created an **empty database** (e.g. `event_manager_free`). The installer connects to it but does not create the database for you.

You do **not** need to configure `DB_*` in `.env` beforehand — the wizard writes that for you.

#### Installer walkthrough

| Step | URL | What you do |
|------|-----|-------------|
| 1. Requirements | `/install` | Confirms PHP version, extensions, and folder permissions. Click **Continue** when all checks pass. |
| 2. Configuration | `/install/setup` | Fill in site name, **Site URL** (must match the URL in your browser, including port), admin name/email/password, and MySQL/MariaDB credentials. |
| 3. Run | Submit the form | Click **Run installation**. The wizard writes `.env`, runs `migrate`, seeds data, and locks the installer. This can take a minute — do not refresh the page. |
| 4. Complete | `/install/complete` | Save the admin email and password shown on screen, then sign in. |

**Configuration form fields**

- **Site URL** — e.g. `http://eventmanagerfree.test` or `http://127.0.0.1:8000`. Used for Stripe redirects and links; must match how you access the site.
- **Administrator** — creates the first admin user; credentials are also saved to `.env` as `ADMIN_*`.
- **Database** — MySQL/MariaDB host, port, database name, username, and password. Use host `127.0.0.1` (not `localhost`) on Windows/Laragon if you see connection errors.
- **Erase existing tables** — optional. Runs `migrate:fresh` and **drops every table** in the chosen database. Only use on a fresh or leftover database.

#### After installation

| URL | Purpose |
|-----|---------|
| `/admin/login` | Admin dashboard |
| `/` | Public homepage |

The installer creates `storage/app/install.lock` and blocks `/install` on future visits.

If you set up the app manually (CLI migrations) and want to disable the wizard:

```bash
php artisan install:lock
```

#### Laragon quick start

```bash
cd D:\laragon\www\EventManagerFree
composer install
npm install
npm run build
```

1. Create database `event_manager_free` in Laragon (MySQL).
2. Add a virtual host with document root `…/EventManagerFree/public` (e.g. `http://eventmanagerfree.test`).
3. Open **`http://eventmanagerfree.test/install`** and complete the wizard.

For a quick test without a vhost: `php artisan serve` → open **`http://127.0.0.1:8000/install`**.

#### Installer troubleshooting

| Issue | Fix |
|-------|-----|
| `/install` returns 404 | Web root must be `public/` |
| 419 Page Expired on submit | Go back to `/install/setup` and submit again; do not open `/install/finish` directly |
| Database connection failed | Start MySQL; create the database; use `127.0.0.1` as host |
| Blank styles | Run `npm run build` |
| Re-run installer (dev only) | Delete `storage/app/install.lock`, then visit `/install` again |

---

### Option B — Manual installation (CLI)

Use this if you prefer the command line or cannot use the web installer.

```bash
# 1. Dependencies & env
composer install
cp .env.example .env
php artisan key:generate

# 2. Configure DB_* and ADMIN_* in .env, then:
php artisan migrate --force
php artisan db:seed --force

# 3. Frontend assets
npm install
npm run build

# 4. Lock the installer (optional, if setup is complete)
php artisan install:lock
```

Create an admin user via the seeder values in `.env` before seeding, or use tinker:

```bash
php artisan tinker
```

Default seeder credentials (if set in installer/seeder): check `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`.

---

## Post-installation setup

### Stripe payments

1. Sign in to **Admin → Settings → Payment settings**.
2. Enable **Stripe**.
3. Enter **Publishable key**, **Secret key**, and **Webhook signing secret** from your [Stripe Dashboard](https://dashboard.stripe.com/).
4. Add a webhook endpoint pointing to:

   ```
   https://your-domain.com/stripe/webhook
   ```

5. Optional in `.env`:

   ```env
   STRIPE_CURRENCY=usd
   ```

### Email (SMTP)

Configure under **Admin → Settings → Email settings**, or set standard Laravel mail variables in `.env`.

### File uploads

Uploads are stored in `public/uploads/`. Ensure that folder is writable by the web server.

---

## Useful URLs

| URL | Description |
|-----|-------------|
| `/` | Public homepage |
| `/events` | Event listing |
| `/admin/login` | Admin sign-in |
| `/admin/dashboard` | Admin dashboard (includes **Purchase Pro** link) |
| `/documentation/index.html` | Bundled HTML documentation |
| `/install` | Web installer (disabled after setup) |

---

## Local development (Laragon)

See **[Install using the web installer → Laragon quick start](#laragon-quick-start)** above for the full steps.

For day-to-day development after setup:

```bash
composer install
npm run dev   # or npm run build for production assets
php artisan serve   # optional, if not using Laragon vhost
```

---

## Upgrade to Event Manager Plus

Get the commercial edition with all features enabled:

**[https://lucrativeit.com/products/event-manager-plus](https://lucrativeit.com/products/event-manager-plus)**

---

## Troubleshooting

| Issue | What to try |
|-------|-------------|
| 500 error after deploy | Check `storage/logs/laravel.log`; ensure `storage/` and `bootstrap/cache/` are writable |
| Installer requirements fail | Enable missing PHP extensions in `php.ini` and restart the web server |
| Stripe redirect fails locally | Set `APP_URL` to match the browser URL exactly (host + port) |
| Blank styles | Run `npm run build` and hard-refresh the browser |
| `/install` not found | Confirm the web root is the `public/` directory |

---

## License & support

- **Event Manager Free** — [MIT License](LICENSE)
- **Event Manager Plus (premium)** — [LucrativeIt](https://lucrativeit.com/products/event-manager-plus)

Report issues with the free edition on GitHub Issues. For the premium product, use the link above.
