# Joreption — Features

A bilingual (English / Arabic, with RTL) Cash-on-Delivery e-commerce platform migrated from a WhatsApp group to a live web site at <https://joreption.com>.

Built with Laravel 11 + Filament 3 + Tailwind 3 + Alpine.js + MariaDB 10.11. Hosted on Contabo Cloud VPS 10, Ubuntu 24.04 LTS.

_Last updated: 2026-05-20_

---

## Customer site (public)

### Catalog & browsing
- Product grid: responsive 2 / 3 / 4 columns
- Hero banner with brand color, "Deals" pill, "Browse Catalog" CTA
- Search box (matches `name_en`, `name_ar`, `description_en`, `description_ar`)
- Category chip filter (horizontally scrollable on mobile)
- Stock badges (`Out of Stock`, `X left`)
- Pagination (12 per page)
- Empty-state with "Clear filters"

### Product detail
- Breadcrumb
- Large image with hover zoom
- Locale-aware name + description (auto-picks Arabic or English)
- Live "X left" indicator (green dot)
- "Cash on Delivery" info pill
- Alpine.js quantity stepper (− / +)
- "Add to Cart" CTA with cart icon

### Cart
- Session-based multi-item cart (`App\Services\Cart`)
- Per-item quantity stepper (auto-submits on change)
- Per-item remove button
- "Clear cart" link
- Sticky order summary aside (subtotal, payment method, total)
- 3-step indicator: Cart → Shipping → Confirmation

### Checkout
- Single-page form: full name, phone, city, address, notes
- Pre-fills from logged-in customer's saved profile
- Guest checkout (no signup required)
- Optional sign-in CTA at top: _"Have an account? Sign in for faster checkout, or continue as guest."_
- Amber "Cash on Delivery" callout
- Sticky order summary with per-item thumbnails
- Single DB transaction: order + items + stock decrement

### Order confirmation
- Green checkmark hero with order # + 4-column meta grid (date, total, payment, items)
- Itemized list with thumbnails
- WhatsApp deep-link to admin (when `admin_whatsapp` setting is set)
- "Continue Shopping" CTA
- Tip footer with `/track` URL + order # to bookmark

### Track-my-order (no login)
- `/track` form: phone + order ID
- Phone matching is fuzzy (strips spaces / dashes / parens)
- Returns status pill (Pending / Confirmed / Delivered / Cancelled) + items + total
- **Rate-limited:** 10 attempts per IP per minute

### Customer accounts (optional)
- `/register` — name + email + phone + password (with confirmation) + optional city/address
- `/login` — email + password, "Remember me", "Forgot password?" link
- `/forgot-password` — sends reset link (currently logged to file; needs SMTP provider)
- `/reset-password/{token}` — new password + confirm
- `/logout` — POST, clears session
- `/my-orders` — list of customer's past orders with status badges
- `/my-orders/{id}` — single order detail (auth + ownership-guarded)
- **Smart phone merge:** registering with an existing-order phone links those guest orders to the new account

### Header / navigation
- Sticky navbar with logo + brand name
- Guest sees: Catalog · Track Order · **Sign in** + **Register**
- Authenticated customer sees: Catalog · My Orders · avatar dropdown (My Orders / Track Order / Logout)
- Logged-in admin sees: Dashboard link

### Localization
- English + Arabic in `lang/en.json` + `lang/ar.json`
- RTL flip via `dir="rtl"` on `<html>` when locale is `ar`
- Logical Tailwind utilities (`ps-*`, `me-*`, `start-*`) — no extra plugin
- `?lang=ar` / `?lang=en` query string toggle persists in session
- Header has visible language switcher

### Branding
- Deep teal palette (`brand-{50…950}`) matching the logo background
- Red accent (`accent-{50…900}`) for "Deals" and sale moments
- Inter font (Figtree fallback)
- Logo: `public/images/logo.jpg`

### Currency
- Admin-configurable in `/admin/settings`:
  - Currency code (e.g., USD, JOD, AED, EUR)
  - Symbol ($, JD, د.إ, €)
  - Position (before vs after amount)
- Global helper `money_format($amount)` used everywhere prices render
- Currently configured: **JOD / JD / after** (e.g., `24.99 JD`)

### Coming Soon mode
- Toggle in `/admin/settings`
- When on: every public visitor sees a branded splash page
- Admins (logged in) **bypass** automatically
- Custom EN/AR headlines configurable

---

## Mobile admin — Quick Add (PWA)

- Installable as a Progressive Web App (home-screen icon, full-screen, native feel)
- Service worker (`public/sw.js`) caches the shell for offline opens
- `start_url = /admin/quick-add`, deep teal theme color

### Capture flow
- Big "📷 Take Photo" CTA opens rear camera (`capture="environment"`)
- Multiple photos can be selected at once → queued
- Client-side compression: 1280px max dimension, JPEG quality 0.85 (5 MB phone photos → ~300 KB)
- "Retake" overlay on the image preview
- Position indicator (`2 / 5`) for burst queues
- "Add more photos" button at the bottom

### Form
- **Category dropdown** (when categories exist; persists last-used across burst)
- Bilingual name fields (Arabic on top, RTL; English below, LTR)
- Bilingual description textareas
- **🎤 Voice input** on the Arabic description (Web Speech API, `ar-JO` locale)
- Price input with currency symbol prefix
- Stock number input
- "Publish immediately" toggle (default on)

### After publish
- Big success card with:
  - Green checkmark icon
  - Product name
  - **Full product URL displayed**
  - **"📋 Copy link"** button → puts URL on clipboard (with "Copied! Paste into WhatsApp" feedback)
- Two follow-up CTAs: "📷 New photo" / "Next →" (or "Done" if queue empty)

---

## Admin dashboard (`/admin` — Filament 3)

### Products
- Bilingual name + description (EN / AR)
- Image upload (stored in `storage/app/public/products/`)
- Price + stock + active toggle
- Category selector
- **Copyable public URL** in row ("Copy link" — for WhatsApp sharing)

### Orders
- Sortable list with status filter (Pending / Confirmed / Delivered / Cancelled)
- Per-row editable status (select-column)
- Items relation manager (line items per order)
- **Pending-orders count badge** in the sidebar

### Customers
- Name + Phone (copyable) + City
- Orders count (badge) + Total spent (sum, currency-formatted)
- "Has orders" filter toggle
- Detail page with embedded **Order History** relation manager

### Categories
- Bilingual name + auto-generated slug from English name
- Drag-to-reorder via position field
- Active toggle
- Products-count badge

### Settings
- Currency (code / symbol / position)
- Admin WhatsApp number
- Coming Soon mode toggle + custom EN/AR headlines
- Backed by a key/value `settings` table with cached reads (forgotten on save)

### Profile
- `/admin/profile` — change name, login email, password

### Auth
- Filament-default email/password login
- `User` model implements `FilamentUser` (required in production)
- Separate `web` guard (admin) from `customer` guard (public)

---

## WhatsApp import

Artisan command `php artisan whatsapp:import {path}` parses a WhatsApp chat export folder (`.txt` + `IMG-*.jpg` files):

- Filters to admin sender (default `JorEption`, configurable via `--sender`)
- Groups consecutive messages into product posts via price-line detection
- Regex-extracts:
  - Price: `*السعر X دينار*`, `سعر العرض X دينار`, `X JD`
  - Stock: `قطعة واحدة فقط`, `متوفر X حبة فقط`, `باقي X`, `جددنا الكمية X`
- Skips noise: `<Media omitted>`, `سعره بالسوق`, country flags, `Online X$`
- Matches images by date + chronological cursor
- UTF-8 cleanup (handles truncated emoji bytes that broke MariaDB inserts)
- Options:
  - `--dry-run` — preview without writing
  - `--with-image-only` — skip products with no matched image
  - `--activate` — create as active instead of drafts (default = drafts)
  - `--limit=N` — first N products only

**Initial run imported 38 products as drafts from the project's actual WhatsApp group export.**

---

## Infrastructure

### Server
- **Contabo Cloud VPS 10** — 4 vCPU / 8 GB RAM / 145 GB NVMe — hostname `vmi3308944` — IP `178.18.244.125`
- Ubuntu 24.04 LTS, Asia/Amman timezone
- Nginx 1.24 + PHP 8.3-FPM + MariaDB 10.11 + Redis 7

### TLS
- HTTPS via Let's Encrypt
- Auto-renew via `certbot.timer`
- HTTP → HTTPS 301 redirect

### Security
- SSH: ed25519 key-only authentication (password auth disabled)
- UFW firewall: only 22 / 80 / 443 inbound
- fail2ban active on SSH
- Admin and Adminer passwords rotated post-setup

### Web DB admin
- Adminer at `/_adminer`, protected by HTTP Basic auth
- Credentials in `/root/.joreption-secrets` on the server (rotate as needed)

### Backups
- Daily MariaDB dump at 03:00 (`/usr/local/bin/joreption-backup.sh`)
- Stored in `/var/backups/joreption/`, gzipped
- 14-day retention (older files auto-removed)

### Production tuning
- Cached config / routes / views
- OPcache enabled (PHP-FPM reload runs as part of every deploy)
- Redis used for sessions + cache (`SESSION_DRIVER=redis`, `CACHE_STORE=redis`)
- Log driver: `daily` (rotated)

---

## Architecture

### Data model
- `users` (Filament admins) — separate from `customers`
- `customers` (public-site accounts) — has nullable email / password
- `categories` — bilingual + slug + position + active
- `products` — bilingual + price + stock + image + active + category
- `orders` — customer + phone + city + address + notes + status + total + COD
- `order_items` — order + product + product_name + unit_price + quantity + line_total
- `settings` — key/value, cached
- `password_reset_tokens` — used by both `users` and `customers` brokers

### Auth guards
- `web` (default): `User` model — Filament admin
- `customer`: `Customer` model — public site optional login
- Both can be logged in simultaneously; sessions are independent

### Routes (web)
| Path | Auth | Purpose |
|---|---|---|
| `/` | guest | Catalog with search + category filter |
| `/products/{product}` | guest | Product detail |
| `/cart` | guest | Cart |
| `/checkout` | guest | Checkout form |
| `/orders/{order}/confirmation` | guest | Order confirmation |
| `/track` | guest | Track-my-order lookup |
| `/login` `/register` `/forgot-password` `/reset-password/{token}` | guest | Customer auth |
| `/my-orders` `/my-orders/{order}` | customer | Logged-in customer's orders |
| `/admin` | filament panel | Filament dashboard |
| `/admin/quick-add` | web (admin) | Mobile Quick Add PWA |
| `/admin/profile` | filament | Change own password |
| `/_adminer` | nginx basic auth | Web DB admin |

---

## Roadmap (not yet started)

- **Email provider** — Resend / Brevo / Mailgun → password-reset emails actually send
- **Admin order notifications** — Telegram bot or email ping on new COD orders
- **Activate the 38 imported drafts** — admin reviews names + sets stock + assigns categories + toggles Active
- **Visual polish** — Metronic extras: brand strip on home, "save X%" discount badges, related-products section on detail page
- **SMS / WhatsApp Business API** — phone-OTP login + outbound order status SMS
- **Push-based deploy** — git remote + `deploy.sh` on server (currently tar + scp)
- **Mobile admin polish** — voice input for English description, image swap during edit, draft-only mode
- **Customer wishlist** — save products without ordering
- **Order CSV export** for admin
- **Reviews / ratings** on products
