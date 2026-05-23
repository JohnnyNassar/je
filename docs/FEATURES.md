# Joreption — Features

A bilingual (English / Arabic, with RTL) Cash-on-Delivery e-commerce platform migrated from a WhatsApp group to a live web site at <https://joreption.com>.

Built with Laravel 11 + Filament 3 + Tailwind 3 + Alpine.js + MariaDB 10.11. Hosted on Contabo Cloud VPS 10, Ubuntu 24.04 LTS.

_Last updated: 2026-05-24_

---

## Customer site (public)

### Catalog & browsing
- Product grid: responsive 2 / 3 / 4 columns
- Hero banner (locked 21:9 on desktop) with brand color, "Deals" pill, "Browse Catalog" CTA
- Hero background: admin can upload a custom image (with in-browser cropper) OR feature one of the products' images — pick from a server-side searchable dropdown
- Search box (matches `name_en`, `name_ar`, `description_en`, `description_ar`)
- Category chip filter (horizontally scrollable on mobile)
- **"Featured" strip** below the hero — shows up to 8 products marked as featured (horizontal scroll, snap-aligned)
- Stock badges (`Out of Stock`, `X left`)
- **"Save X%" red ribbon** on any product where `compare_at_price > price`, plus crossed-out original price on cards + detail
- Pagination (12 per page)
- Empty-state with "Clear filters"

### Product detail
- Breadcrumb
- Large image with hover zoom
- Locale-aware name + description (auto-picks Arabic or English)
- Live "X left" indicator (green dot)
- **Variant selector** — when a product has variations, option chips live-swap the price, stock, and image, and set the chosen variant (out-of-stock options disabled)
- "Cash on Delivery" info pill
- Alpine.js quantity stepper (− / +)
- "Add to Cart" CTA with cart icon
- **"You may also like"** — strip of same-category products at the bottom

### Cart
- Session-based multi-item cart (`App\Services\Cart`) — each line keyed by **product + variant**
- Per-item quantity stepper (auto-submits on change)
- Variant label + variant image per line
- **"Add another option"** — quick-add buttons for a product's other in-stock variants, without leaving the cart
- **Coupon code** entry (apply / remove) with the discount reflected live
- Per-item remove button
- "Clear cart" link
- Sticky order summary aside (subtotal, discount, payment method, total)
- 3-step indicator: Cart → Shipping → Confirmation

### Checkout
- Single-page form: full name, phone, city, address, notes
- Pre-fills from logged-in customer's saved profile
- Guest checkout (no signup required)
- Optional sign-in CTA at top: _"Have an account? Sign in for faster checkout, or continue as guest."_
- Amber "Cash on Delivery" callout
- Sticky order summary with per-item thumbnails, variant labels, and Subtotal / Discount / Total
- Single DB transaction: order + items (with variant snapshot) + per-variant stock decrement + coupon usage increment

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
- Image upload (stored in `storage/app/public/products/`) — auto-resized server-side to max 1600px, JPEG q85 via GD
- **Pick existing image from media library** as an alternative to upload — opens a 4-column thumbnail grid modal
- Price + stock + active toggle
- **Sale price** (`compare_at_price`) — when higher than current price, triggers Save% badge on public site
- **Featured toggle** — surfaces the product in the Featured strip on the catalog home
- Category selector (server-side searchable, no preload)
- **Variations** — collapsible repeater of color/size options, each with its own stock + optional price/image override (drag-to-reorder); product stock auto-sums from them
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

### Coupons
- Code (case-insensitive), percentage or fixed amount
- Optional minimum order, usage limit (shows `used / max`), start / expiry dates, active toggle
- Applied at checkout from the cart; discount + code recorded on the order

### Staff (admin accounts)
- Single-seller roles: **admin** (full access) vs **staff** (catalog only — Products, Categories, Media, Quick Add)
- Admin-only Staff manager to create/edit accounts (name / email / role / password; can't delete your own account)
- Staff are blocked (hidden nav + 403) from Orders, Customers, Coupons, Settings, and Staff itself

### Settings
- Currency (code / symbol / position)
- Admin WhatsApp number
- Coming Soon mode toggle + custom EN/AR headlines
- **Landing page hero** — three sources, in priority order:
  1. Custom upload (FileUpload with built-in in-browser image cropper for 21:9 / 16:5 / 3:1 / 16:9 / free aspect ratios)
  2. Pick from media library (prominent button — same modal as the product picker)
  3. Feature any existing product image (server-side searchable Select with live thumbnail preview)
- Backed by a key/value `settings` table with cached reads (forgotten on save)

### Media Library — `/admin/media-library`
WordPress-style image manager:
- **Stats row**: total files, total disk usage, orphan count
- **Grid**: 6 columns (responsive) of thumbnails from `storage/app/public/products/` + `/hero/`
- **Filters**: search by filename · all / used / orphans · sort newest/oldest/largest/smallest/name
- **Each tile**: orphan badge (red) or "N× used" badge (green), filename, size, hover-to-view
- **Modal**: full preview, copy URL, open full, delete-if-orphan (whitelisted to allowed folders, protected from deleting in-use files)
- **Pagination**: 48 per page

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

### Deployment (push-based via GitHub)
- **Repo**: <https://github.com/JohnnyNassar/je> (private)
- **Local workflow**: edit → `git add . && git commit && git push`
- **Trigger a deploy**: `ssh root@178.18.244.125 /usr/local/bin/joreption-deploy.sh`
- **Deploy script** at `/usr/local/bin/joreption-deploy.sh`:
  - `git fetch --all && git reset --hard origin/main`
  - `composer install --no-dev --optimize-autoloader`
  - `npm ci && npm run build` (skippable with `--skip-npm`)
  - `php artisan filament:upgrade` (republishes Filament's compiled JS/CSS)
  - `php artisan migrate --force`
  - Caches config / routes / views
  - `systemctl reload php8.3-fpm` (clears OPcache)
- **Server's GitHub access**: ed25519 deploy key at `/root/.ssh/joreption_deploy`, read-only access to the repo, configured via `~/.ssh/config`
- **Skip flags**: `--skip-npm` (no JS changes) · `--skip-composer` (no composer.lock changes) → ~3× faster
- **Untracked on server (preserved across deploys)**: `.env`, `vendor/`, `node_modules/`, uploaded images, Filament's published assets

---

## Architecture

### Data model
- `users` (Filament admins) — separate from `customers`; **`role`** (admin | staff)
- `customers` (public-site accounts) — nullable email / password
- `categories` — bilingual + slug + position + active
- `products` — bilingual + price + **compare_at_price** + stock + image + active + **is_featured** + category
- `product_variants` — product + name + stock + optional price/image override + position (product stock = sum of these)
- `coupons` — code + type (percent | fixed) + value + min order + usage limit/count + start/expiry + active
- `orders` — customer + phone + city + address + notes + status + total + **discount_total** + **coupon_code** + COD
- `order_items` — order + product + **variant** (id + name snapshot) + product_name + unit_price + quantity + line_total
- `settings` — key/value, cached (includes `hero_image_path`, `hero_product_id`, `coming_soon_*`, currency)
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
| `/admin/media-library` | filament panel | WordPress-style image manager |

---

## Helpers / support classes

- `App\Support\ImageResizer::fit($path, $maxDim, $quality)` — in-place GD-based resize; no-op for files already smaller than the threshold. Called automatically from `Product::saved()` and the Settings hero save.
- `App\Concerns\HandlesMediaPicking` — Livewire trait providing `pickMediaToState($statePath, $path)`. Used in `Settings`, `EditProduct`, `CreateProduct` pages so the media-picker modal can route picks through `form->fill()` (which triggers FileUpload hydration), then dispatches `close-modal`.
- `money_format($amount)` — global helper; reads currency settings.
- Filament render hook: `panels::head.end` injects `<script src="/js/media-picker.js?v=...">` so the Alpine `mediaPicker` component is registered on every admin page (modal-injected content can't run its own `<script>`).

---

## Roadmap (not yet started)

- **Email provider** — Resend / Brevo / Mailgun → password-reset emails actually send
- **Admin order notifications** — Telegram bot or email ping on new COD orders
- **Activate the 38 imported drafts** — admin reviews names + sets stock + assigns categories + toggles Active
- **Bidding / auctions** — design pending (timed auction vs. "make an offer")
- **Loyalty / rewards** — points for frequent buyers, redeemable as a discount (builds on coupons)
- **Ad / banner areas** — managed promo slots beyond the single hero
- **Fuller customer info** — structured address + multiple saved addresses
- **Variant quick-quantity grid** — set quantities for several variants at once on the product page
- **SMS / WhatsApp Business API** — phone-OTP login + outbound order status SMS
- **Mobile admin polish** — voice input for English description, image swap during edit, draft-only mode; variant entry in Quick Add
- **Customer wishlist** — save products without ordering
- **Order CSV export** for admin
- **Reviews / ratings** on products
- **Webhook-triggered auto-deploy** — push to GitHub → server auto-pulls (currently a manual SSH command)
