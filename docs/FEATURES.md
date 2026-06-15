# Joreption — Features

A bilingual (English / Arabic, with RTL) Cash-on-Delivery e-commerce platform migrated from a WhatsApp group to a live web site at <https://joreption.com>.

Built with Laravel 11 + Filament 3 + Tailwind 3 + Alpine.js + MariaDB 10.11. Hosted on Contabo Cloud VPS 10, Ubuntu 24.04 LTS.

_Last updated: 2026-06-15_

---

## Customer site (public)

### Catalog & browsing
- Product grid: responsive 2 / 3 / 4 columns
- Hero banner (locked 21:9 on desktop) with brand color, "Deals" pill, "Browse Catalog" CTA
- Hero background: admin can upload a custom image (with in-browser cropper) OR feature one of the products' images — pick from a server-side searchable dropdown
- Search box (matches `name_en`, `name_ar`, `description_en`, `description_ar`)
- **2-level category filter** — a row of top-level categories and, when one is active, a second row of its sub-categories (both horizontally scrollable on mobile). Picking a parent shows its own products **plus** all of its sub-categories'; picking a child narrows to that child
- **"Featured" strip** below the hero — shows up to 8 products marked as featured (horizontal scroll, snap-aligned)
- Stock badges (`Out of Stock`, `X left`)
- **"Save X%" red ribbon** on any product where `compare_at_price > price`, plus crossed-out original price on cards + detail
- Pagination (12 per page)
- Empty-state with "Clear filters"

### Product detail
- Breadcrumb
- **Image gallery** — cover image plus optional extra photos; a thumbnail strip swaps the main image (shown only when there's more than one)
- Locale-aware name + description (auto-picks Arabic or English)
- Live "X left" indicator (green dot)
- **Variant selectors** — two ways, depending on the product:
  - **Structured options** — up to 3 attributes (e.g. Colour, Size, Dimension) each render a **separate selector**; picking values resolves to the matching variant and live-swaps price / stock / image. Combinations with no in-stock variant are auto **struck-through**
  - **Flat list** (legacy / single-axis) — option chips that live-swap price, stock and image (out-of-stock disabled)
- **Staff-only "Product #N" tag** — shown under the breadcrumb to logged-in staff/admins only (never to shoppers), for cross-referencing with the admin
- **Draft preview** — staff viewing an inactive product see an amber "Draft preview — customers can't see this yet" banner instead of a 404
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
- **Tier pricing:** a logged-in **wholesale** customer automatically pays a configurable % off every unit price (flows cart → checkout → order line items)

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

### Loyalty points (optional)
A per-customer rewards program: **every customer has their own points balance and a full earn / redeem history** (ledger), tied to their orders.
- **Earning** — credited automatically when an order is marked **delivered** (`order total × earn rate`, plus any active promotion). Every order is attached to a customer — their account, or a record auto-created from their phone for guest checkouts — so guests accrue points too. **VIP**-tier customers earn at a configurable multiplier (e.g. ×2)
- **Redeeming** — at checkout a **logged-in** customer can opt in (checkbox, live total update) to spend points for a discount; **stacks with a coupon**, capped at the order amount and the minimum-redeem setting. Guests must sign in to spend (the phone-merge then links their prior orders to the account)
- **Promotions** — admin-run boosts (double / triple, or bonus points; optionally for a date window or above a minimum order) automatically increase what an order earns
- Points balance card on **My Orders**; "you'll earn X points" hint at checkout (reflects active promotions)
- Off by default; rates, promotions and reports live in the admin **Loyalty** section

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

## Mobile admin — Quick Add (PWA, admin-only)

- **Admin-only** — restricted to admins / super admins (`abort 403` for staff); not part of the staff catalog tools
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

### Dashboard (home, admin-only)
- Stats row: orders (+ pending count), revenue from delivered orders, customers, low-stock alert
- 14-day orders line chart (brand teal)
- "Latest orders" table (click through to the order)
- Full-width admin layout (Filament's default ~1280px cap removed)
- Wide list tables (orders, products, activity log…) keep an **always-visible horizontal scrollbar** so off-screen columns are easy to reach
- In-app **notification bell** — admins get an alert on every new order

### Notifications (admin-only)
- Config page for channels: **in-app (Dashboard)**, **Email** (SMTP), **SMS** (provider key/secret/from), **WhatsApp** (Cloud API id + token)
- In-app channel is live (new-order bell alerts). **Email is wired** — saved SMTP settings drive Laravel mail at runtime, with a "Send test email" button; powers password-reset emails once a relay's credentials are entered. SMS/WhatsApp capture settings now, sending wired per channel once provider credentials are supplied.

### Help & user guides (admin)
- In-admin **Help** page: a full plain-language guide to every area of the platform, for the owner and staff
- **Getting Started** page (top of the sidebar, all admins): a bilingual **step-by-step onboarding guide** with an **English ⇄ العربية toggle** (choice remembered) — Arabic renders full RTL. Numbered cards cover sign-in/password, adding a product, variations, categories, media and tips. Tailored to catalog work, so it's the friendly first stop for a new staff member

### Products
- Bilingual name + description (EN / AR)
- **Cover image** upload (stored in `storage/app/public/products/`) — auto-resized server-side to max 1600px, JPEG q85 via GD
- **Image gallery** — additional photos via a multiple, drag-reorderable upload (also auto-resized); shown as a thumbnail strip on the storefront. The cover image stays the one used in grids/cart/orders
- **Pick existing image from media library** as an alternative to upload — opens a 4-column thumbnail grid modal
- Price + stock + active toggle
- **Sale price** (`compare_at_price`) — when higher than current price, triggers Save% badge on public site
- **Featured toggle** — surfaces the product in the Featured strip on the catalog home
- **Cost price + profit** (gated) — record what you paid per unit; toggleable **Cost** and **Profit** columns show the margin (`amount (margin %)`, green for profit / red for loss). **Never shown to customers**; visible to admins and any staff granted cost access
- Category selector (server-side searchable, no preload)
- **Structured options + variations** (multi-axis):
  - **Options** section — define up to **3 attributes** (Colour / Size / Dimension), each with bilingual values
  - **"Build combinations"** action — generates one variation per combination (cartesian product), preserving stock/price/photo already set
  - **Variations** repeater — each combination has its own stock + optional price/image override; product stock auto-sums from them. Still supports manual single-axis variations for simple products
- **"View on website"** row + edit-page action — opens the product's public page in a new tab (works even for drafts, via the staff preview)
- **Total products** shown as a sidebar badge **and** on the list page ("N products total · M active")
- **Sortable columns** (name, price, stock, dates…) + show/hide column toggles; newest-first by default
- **Copyable public URL** in row ("Copy link" — for WhatsApp sharing)

### Orders
- Sortable list with status filter (Pending / Confirmed / Delivered / Cancelled)
- Per-row editable status (select-column)
- Items relation manager (line items per order)
- **Pending-orders count badge** in the sidebar

### Customers
- Name + Phone (copyable) + City
- **Tier** badge — Regular / VIP / Wholesale — set per customer; drives wholesale pricing + VIP point multiplier; filterable
- Orders count (badge) + Total spent (sum, currency-formatted)
- "Has orders" filter toggle
- Detail page with embedded **Order History** relation manager

### Categories
- **2-level hierarchy** — a **Parent category** selector makes a category a sub-category (capped at 2 levels: the parent field excludes self and is disabled once a category has its own children). Parent badge column ("— top level —" when none)
- Bilingual name + auto-generated slug from English name
- Drag-to-reorder via position field
- Active toggle
- Products-count badge
- A **standard taxonomy** (10 parents / 40 bilingual sub-categories — Electronics, Home & Kitchen, Men's/Women's Fashion, Kids & Baby, Beauty, Sports, Health, Automotive, Garden & Tools) is seeded via `CategorySeeder` (idempotent)

### Coupons
- Code (case-insensitive), percentage or fixed amount
- Optional minimum order, usage limit (shows `used / max`), start / expiry dates, active toggle
- Applied at checkout from the cart; discount + code recorded on the order

### Staff (admin accounts)
- Three roles: **super admin** (owner — full access), **admin** (everyday ops), **staff** (catalog only — Products, Categories, Media)
- **Super-admin-only** (hidden nav + 403 for everyone else): Staff/user management, Settings (Store / Notifications / Loyalty) and the Activity log
- **Admin** can run Orders, Customers, Coupons and Loyalty, but not the super-admin-only areas above
- **Staff** are limited to the catalog
- Super-admin-only Staff manager to create/edit accounts (name / email / role / password; can't delete your own account). Because only a super admin manages staff, an admin can't promote anyone to super admin
- **"Can view cost prices & profit"** per-user toggle — grant a single **Staff** member access to product cost/profit without promoting them to admin (admins always see it)

### Loyalty (dedicated nav section, admin-only)
- **Points activity** — at-a-glance **reports** (points outstanding, their currency **liability value**, earned / redeemed over 30 days, plus a 6-month earned-vs-redeemed chart) above a read-only ledger of every earn / redeem / adjust, filterable by type
- **Promotions** — time-boxed point boosts: **multiply points** (×2 double, ×3 triple) or **bonus points** per order, with optional minimum-order total and start/end dates, an on/off toggle, and a live Running / Scheduled / Ended / Off status. The best active promo is auto-applied when an order is delivered (and previewed at checkout)
- **Settings** — enable toggle, earn rate (points per currency), point value (currency per point), minimum redeem, **VIP points multiplier**
- Per-customer **points** column + manual **"Adjust points"** action on Customers (records a ledger entry); points-earned / redeemed columns on Orders

### Settings
- Currency (code / symbol / position)
- Admin WhatsApp number
- **Customer tiers** — *Wholesale discount %* (here, on Store settings) and *VIP points multiplier* (on Loyalty settings) drive the per-tier perks
- Coming Soon mode toggle + custom EN/AR headlines
- **Google Analytics (GA4)** — paste your Measurement ID (`G-XXXX`) to load Google's tag on the storefront + Coming Soon page for visitor tracking
- **Privacy Policy page** (`/privacy`, bilingual) + a dismissible **cookie notice** — reachable even while Coming Soon is on, and linked in the footer
- **Landing page hero** — three sources, in priority order:
  1. Custom upload (FileUpload with built-in in-browser image cropper for 21:9 / 16:5 / 3:1 / 16:9 / free aspect ratios)
  2. Pick from media library (prominent button — same modal as the product picker)
  3. Feature any existing product image (server-side searchable Select with live thumbnail preview)
- Backed by a key/value `settings` table with cached reads (forgotten on save)

### Activity log (System → Activity log, super-admin-only)
- An audit trail of **deliberate admin/staff actions**: create / update / delete on products, orders, coupons, promotions, categories, settings and staff accounts — each with **who did it**, the **before → after** values, and a timestamp
- **Auth events:** admin/staff logins, logouts and failed logins, plus **customer storefront logins / logouts** (type `customer-auth`; customer *failed* logins are skipped to avoid bot noise)
- **Storefront order placements** are logged too (event `placed`, attributed to the customer or marked guest) — the one customer-driven write that's tracked
- Every entry records the **client IP** and **device / user-agent**
- Secret values (mail password, API keys) are **redacted**; other storefront/customer-driven writes are not logged, keeping it a clean accountability trail
- Read-only page, filterable by event type and source — a lightweight built-in (no third-party package)

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
- `users` (Filament admins) — separate from `customers`; **`role`** (super_admin | admin | staff) + **`can_view_cost`** flag
- `customers` (public-site accounts) — nullable email / password + **points_balance** + **tier** (regular | vip | wholesale)
- `categories` — bilingual + slug + position + active + **`parent_id`** (self-FK for the 2-level hierarchy; null = top-level)
- `products` — bilingual + price + **cost_price** (admin-only) + **compare_at_price** + stock + image + **`gallery`** (JSON list of extra image paths) + active + **is_featured** + category
- `product_options` — product + **name_en/name_ar** + **`values`** JSON (`[{en,ar}]`) + position (the up-to-3 variant axes: Colour / Size / Dimension)
- `product_variants` — product + name + **`option_values`** JSON (`{"Colour":"Red","Size":"M"}`; null for legacy flat variants) + stock + optional price/image override + position (product stock = sum of these)
- `coupons` — code + type (percent | fixed) + value + min order + usage limit/count + start/expiry + active
- `orders` — customer + phone + city + address + notes + status + total + **discount_total** + **coupon_code** + **points_earned** + **points_redeemed** + COD
- `order_items` — order + product + **variant** (id + name snapshot) + product_name + unit_price + quantity + line_total
- `loyalty_transactions` — customer + order + points (±) + type (earn | redeem | adjust) + description (the points ledger)
- `loyalty_promotions` — name + type (multiplier | bonus) + multiplier / bonus_points + min_order_total + starts_at / ends_at + active (time-boxed point boosts)
- `settings` — key/value, cached (includes `hero_image_path`, `hero_product_id`, `coming_soon_*`, currency, `google_analytics_id`)
- `activity_logs` — audit trail: log_name + event + description + subject (morph) + causer (morph) + properties (old/new) + **ip_address** + **user_agent** + timestamp
- `password_reset_tokens` — used by both `users` and `customers` brokers

### Auth guards
- `web` (default): `User` model — Filament admin (roles super_admin / admin / staff, gated via the `AdminOnly` and `SuperAdminOnly` traits' `canAccess()`)
- `customer`: `Customer` model — public site optional login
- Both can be logged in simultaneously; sessions are independent
- Sign-ins on **both** guards are written to the activity log (with IP + user agent)

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

Prioritised after the 2026-05-25 review — the platform is feature-complete and live behind Coming Soon. _(2026-05-26: admin role tiers, customer tiers with wholesale pricing + VIP point multipliers, and a richer audit log shipped since — see Day 9. 2026-06-11: product cost price + profit with per-user cost access, and an admin/storefront UI density pass — see Day 10. 2026-06-14/15: product image galleries, staff draft preview + staff-only product #, admin products-table UX (top scrollbar, total count, sortable columns), **structured multi-axis variants** (Colour × Size × Dimension), and **2-level categories** with a standard taxonomy — see Days 12–15.)_

### 1. Launch readiness (go-live)
- Review & **activate the imported product drafts** — names, stock, categories, Active toggle _(live: ~37 drafts vs 8 active)_
- Set the **WhatsApp number** _(`admin_whatsapp` is empty on prod)_, branding and hero on the live site
- ✅ **Cash-on-Delivery checkout verified** end-to-end on production (2026-05-26, including wholesale tier pricing)
- **Enable loyalty** on prod if you want the points program / VIP multiplier active _(`loyalty_enabled` is currently off)_
- Flip **Coming Soon off** and announce to the WhatsApp group

### 2. SEO & discoverability
- Verify **Google Search Console** + submit a sitemap
- Per-page **titles / meta descriptions** + Open Graph / Twitter social-share tags

### 3. Outbound order notifications (needs provider accounts)
- **WhatsApp Business API** (Meta Cloud API) and/or an **SMS gateway** for order + status messages — the config UI already exists; this wires the actual sending. Optional phone-OTP login.

### 4. More features
- **Promo / banner areas** — managed promo slots beyond the single hero. _Scoped 2026-06-13 (build next session): a `banners` table + Filament `BannerResource` (image + link to URL/product/category + placement + start/end scheduling + reorder), rendered on the storefront via an `activeFor($placement)` scope and a responsive, RTL-safe partial. Open decisions: placements, single-vs-carousel, per-locale images, link targets, who manages._
- **Fuller customer info** — structured + multiple saved addresses
- **Bidding / auctions** — design pending (timed auction vs. "make an offer")
- **Loyalty follow-ups** — proactive "you have N points" nudges (reporting + promo campaigns already shipped)
- **Variant quick-quantity grid** — set quantities for several variants at once on the product page
- **Customer wishlist** · **product reviews / ratings** · **order CSV export**
- **Mobile admin polish** — voice input for EN description, image swap during edit, draft-only mode; variant entry in Quick Add

### Infrastructure
- **Webhook-triggered auto-deploy** — push to GitHub → server auto-pulls (currently a manual SSH command)
