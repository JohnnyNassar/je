# Joreption — Development Log

Chronological record of major milestones and decisions during the build.

The project began as a WhatsApp selling group (~1000 members) where one admin posted product photos with prices and customers replied to claim items, paying Cash on Delivery. Goal: turn that into a real platform.

Stack chosen: **Laravel 11 + Filament 3 + Tailwind 3 + Alpine.js + MariaDB 10.11** — matches the owner's PHP/XAMPP background, runs cheaply on a small VPS, has a strong admin panel out of the box.

---

## Day 1 — 2026-05-18 (local scaffolding)

- Scaffolded Laravel 11 in `D:\xampp\htdocs\joreption` (XAMPP, PHP 8.2.12, MariaDB 10.4)
- Configured `mariadb` driver in `.env` (vs MySQL 8 default `utf8mb4_0900_ai_ci` collation incompatibility)
- Enabled PHP `intl` extension (required by Filament)
- Installed **Filament 3.3** admin panel
  - Created admin user `admin@joreption.test` (later renamed `admin@joreption.local`)
- Built domain models + migrations:
  - `Product` (bilingual name/description, price, stock, image, is_active)
  - `Customer` (name, phone, city, address)
  - `Order` (customer_id, status, payment_method, total, etc.)
  - `OrderItem` (order_id, product_id, unit_price, quantity, line_total)
- Filament resources for Product + Order (with bilingual fields, image upload, status badges, items relation manager)
- Public Blade catalog (`/`), product detail, single-item order flow

### XAMPP Apache subdirectory deployment
- Site accessed at `http://localhost/joreption`
- Created an Apache `Alias /joreption "D:/xampp/htdocs/joreption/public"` in a separate vhost include
- Added `RewriteBase /joreption` to `public/.htaccess` to prevent internal redirect loops (Apache `AH00124` error)
- Configured Livewire `setUpdateRoute` / `setScriptRoute` in `AppServiceProvider` to honor the URL subpath

### Bilingual setup
- `lang/en.json` + `lang/ar.json`
- `App\Http\Middleware\SetLocale` reads `?lang=` query + persists in session
- All layouts set `dir="rtl"` when locale is `ar`
- Tailwind logical utilities (`ps-*`, `me-*`, `start-*`) — works in RTL with no extra plugin
- Language switcher Blade component

### Cart, checkout, COD
- Replaced single-product flow with session-based **multi-item cart** (`App\Services\Cart`)
- Routes: `/cart`, `/checkout`, `/orders/{order}/confirmation`
- Checkout creates Customer (firstOrCreate by phone) + Order + OrderItems + decrements stock in a single DB transaction

### Currency settings
- Added a key/value `settings` table + `Setting` model with cached reads
- Filament Settings page with currency_code / currency_symbol / currency_position (before vs after)
- Global `money_format($amount)` helper used throughout views and admin tables
- Configured: **JOD / JD / after** (matching the WhatsApp group's pricing in Jordanian dinars)

### WhatsApp import
- Built `php artisan whatsapp:import {path}` command
- Parsed admin's actual chat export: ~4500 messages → 678 product posts via price-line regex
- Image matching via date + chronological cursor (export only had 174 images for the last 7 days)
- Imported **38 products as drafts** (with images) — the remaining ~640 either had no images in the export or required cleanup
- Fixed UTF-8 issue: malformed emoji bytes were breaking MariaDB inserts; added `iconv('UTF-8','UTF-8//IGNORE',$string)` cleanup

---

## Day 2 — 2026-05-19 (deployment)

### VPS provisioning
- Provider: **Contabo Cloud VPS 10** in Germany — 4 vCPU / 8 GB RAM / 145 GB NVMe (~$10/month)
- Ubuntu 24.04 LTS, Asia/Amman timezone
- Installed: Nginx 1.24, PHP 8.3-FPM, MariaDB 10.11, Redis 7, Certbot, ufw, fail2ban, Composer 2.9, Node 20

### DNS + SSL
- DNS at Namecheap: A records for `@` and `www` → `178.18.244.125`
- Let's Encrypt via Certbot — auto-renew via `certbot.timer`
- HTTP → HTTPS 301 redirect

### Deploy
- Tarballed local project (excluding `vendor`, `node_modules`, `.env`, `storage/logs`) — 7.5 MB
- SCP'd to server, extracted to `/var/www/joreption`
- `composer install --no-dev --optimize-autoloader`
- `npm ci && npm run build`
- Generated production `.env` (Redis sessions/cache, daily log rotation, JOD currency defaults)
- `php artisan migrate --force`
- Imported local DB dump to preserve products + settings

### Nginx vhost
- `/etc/nginx/sites-available/joreption` points to `/var/www/joreption/public`
- PHP 8.3 FastCGI socket
- Asset cache-control headers
- Later fix: static-asset regex needed `try_files $uri /index.php?$query_string;` to fall through to Laravel for Livewire's virtual `/livewire/livewire.js`

### Security hardening
- SSH ed25519 key from Windows pushed to server via `plink -pwfile` + explicit `-hostkey` to bypass first-connect host-key prompt
- `/etc/ssh/sshd_config.d/00-joreption-hardening.conf` disables password auth, key-only login
- UFW: 22 / 80 / 443 only
- Daily MariaDB backup cron at 03:00, 14-day retention
- Adminer installed at `/_adminer` with HTTP Basic auth (htpasswd)
- Rotated Filament admin password + Adminer password (later)
- Rotated Contabo root password (later)

### Bug fixes during deploy
- **Filament admin returned 403 in production** — fixed by implementing `FilamentUser::canAccessPanel()` on `User` model (Filament default-denies non-local envs unless explicitly opted in)
- **Login form returned 405 Method Not Allowed** — Livewire JS wasn't loading because the `setUpdateRoute` override in `AppServiceProvider` was being applied unconditionally; on production (no URL subpath) this double-registered the route. Fix: only override when `APP_URL` contains a non-empty path

### Coming Soon page
- Added `coming_soon_enabled` setting + new headlines (EN + AR)
- `ComingSoonMode` middleware: serves a branded splash with the logo to public visitors when enabled; admins bypass
- Toggle and message editor exposed in `/admin/settings`

### Mobile admin (PWA)
- Created `/admin/quick-add` — full-screen Blade page (not Filament-chrome) optimized for phones
- Web app manifest at `public/manifest.webmanifest` with `start_url=/admin/quick-add`
- Service worker at `public/sw.js` — network-first for HTML, cache-first for assets
- Camera capture via `<input type="file" accept="image/*" capture="environment">`
- Client-side image compression to 1280px / JPEG q0.85 via canvas
- Burst queue with per-item edit
- Bilingual name + description fields
- Voice input for Arabic description (Web Speech API, `ar-JO`)
- After publish: shows the new product's public URL + "📋 Copy link" button (clipboard API)

### Redesign — Metronic-style demo1
- Found user's local Metronic v9 install — Tailwind-based (not Bootstrap)
- Delegated extraction of design tokens to an Explore agent (returned color/spacing/radius/typography spec)
- Ported 5 customer pages to Metronic visual style without depending on Metronic's `kt-*` CSS:
  - Catalog index — gradient hero, 4-col grid, hover zoom, stock badges
  - Product detail — drawer-style layout, stock pill, quantity stepper
  - Cart — 3-step indicator + card-style line items + sticky aside
  - Checkout — 3-step indicator + form + summary
  - Order confirmation — 4-column meta grid + green check
- Added Metronic spacing tokens (`5.5`, `6.5`, `7.5`, `8.5`) and shadow scale (`input`, `card`, `card-hover`)

### Brand color rebrand
- Switched from amber to **deep teal** (`brand-{50…950}` palette built around `#0f4248` — the logo background)
- Added **red accent** (`accent-{50…900}`) for "Deals" / sale moments matching "GARAGE SALE" text in the logo
- Hero promoted to dark teal banner with vertical pinstripe + red glow accent + bigger CTA button

---

## Day 3 — 2026-05-20 (auth + categories + polish)

### Track-my-order
- `/track` — public form: order # + phone
- Fuzzy phone match (strips spaces/dashes/parens)
- Rate-limited to 10 attempts/IP/minute
- Linked from header nav, footer, and order confirmation page

### Filament Customer resource
- `/admin/customers` — list with name / phone (copyable) / city / orders count badge / total spent
- Filter: "Has orders" toggle
- Detail page with embedded `OrdersRelationManager`
- Per-order "Open" button jumps to `/admin/orders/{id}/edit`

### WhatsApp share — copy link
- Filament Product table: new "Link" column with `copyable()` — copies full public URL with a "Link copied — paste into WhatsApp" toast
- Quick Add success state: shows the new product's public URL + big "Copy link" button before advancing to next item

### Admin profile / password change
- Enabled Filament's built-in profile via `->profile()` on the panel provider
- Available at `/admin/profile`, accessible via the user-name dropdown in the top-right

### Customer auth (optional accounts)
- Schema migration: added `email`, `email_verified_at`, `password`, `remember_token` to `customers` table
- Customer model now `extends Authenticatable implements CanResetPassword`
- New `customer` auth guard alongside the existing `web` guard for admins
- Separate password broker `customers` (same `password_reset_tokens` table)
- Controllers in `App\Http\Controllers\CustomerAuth\`:
  - `LoginController` (show / login / logout)
  - `RegisterController` (show / register) — **merges with existing guest customer by phone** if a row with no password exists
  - `PasswordResetController` (forgot + reset, using `Password::broker('customers')`)
- `MyOrdersController` (index + show, `auth:customer` middleware)
- Routes: `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/logout`, `/my-orders`, `/my-orders/{order}`
- Header nav now branches on `@guest('customer')` vs `@auth('customer')` — guest sees "Sign in / Register", auth sees an avatar dropdown
- Checkout pre-fills name / phone / city / address from authenticated customer
- `bootstrap/app.php` redirect logic: requests to `/admin*` redirect to `/admin/login`, others redirect to `route('customer.login')`

### Search + categories
- Schema: new `categories` table (bilingual name + slug + position + is_active)
- `products.category_id` foreign key (nullable, `nullOnDelete`)
- `Category` model with locale-aware name accessor and auto-slug from English name
- Filament `CategoryResource` with drag-to-reorder
- Filament `ProductResource` form: Category select (searchable + preload)
- Catalog: search input + horizontally-scrollable category chip strip + combined filtering (`?q=` + `?category=`)
- Quick Add: category dropdown that remembers the last-used value across burst photos

### Documentation
- `docs/features.html` — standalone HTML overview (Tailwind via CDN) for sharing/screenshotting
- `docs/FEATURES.md` — markdown feature inventory (this file lives at `docs/FEATURES.md`)
- `docs/DEVELOPMENT_LOG.md` — this log

---

## Day 4 — 2026-05-21 (visual polish + media library + git deploy)

### Visual polish: sale prices + featured products
- Migration: added `compare_at_price` (decimal nullable) and `is_featured` (boolean) to `products` + index on `(is_active, is_featured)`
- `Product` model: `isOnSale()` helper + `discount_percentage` accessor (% off rounded to int) + `scopeFeatured()`
- Filament Product form: "Original price" input + "Featured on home page" toggle; table got a star icon for featured
- Quick Add (mobile): added "Original price" with **live Save% preview** + "⭐ Featured on home page" toggle
- Catalog index: red "Save X%" ribbon top-start of cards on sale, crossed-out original price below current, **horizontal "Featured" strip below hero** (only when there are featured items + no filters active), product detail mirrors the same styling
- WhatsApp importer: now extracts `Online $X` and `سعره بالسوق X دينار` patterns into `compare_at_price` so future imports auto-tag sale items

### Hero image
- Added `hero_image_path` setting with Filament `FileUpload` + built-in `imageEditor()` cropper (21:9 / 16:5 / 3:1 / 16:9 / free aspects)
- Catalog hero on the landing page locked to `aspect-[21/9]` on desktop, `min-h-[18rem]` mobile, content vertically centred — image always fills cleanly regardless of source aspect
- Created `App\Support\ImageResizer::fit($path, $maxDim, $quality)` (GD-based, in-place, no-op when smaller). Wired into:
  - `Product::saved()` — every product image upload resized to max 1600px, JPEG q85
  - Settings hero save — resized to max 1920px

### Hero from a product image
- Added `hero_product_id` setting + a Filament `Select` that **server-side searches** (`getSearchResultsUsing` + `getOptionLabelUsing`) so it scales past thousands of products
- Catalog Blade resolves: uploaded image first, else product image, else gradient fallback
- Live Placeholder preview shows the selected product's image right next to the picker

### Scaling Filament Selects
- Removed `->preload()` from Product form's Category picker → now server-side searches by `name_en` / `name_ar` / `slug`
- OrderResource's Customer picker now server-side searches by `name` / `phone` / `email` (with the `->searchable(['col', 'col'])` argument form)

### Media library — `/admin/media-library`
- Custom Filament `Page` (uses `WithPagination` trait for paginated grid)
- Scans `storage/app/public/products/` + `/hero/` directories, builds a usage map by querying `products.image_path` + the hero setting
- Stats row at top (total files / disk usage / orphan count)
- Search box (debounced 300ms), filter (all / used / orphan), sort (newest / oldest / largest / smallest / name)
- Per-tile click → modal with preview, "Copy URL", "Open full", "Delete orphan" (whitelisted to allowed folders; in-use files protected from deletion)
- Pagination at 48 per page

### Media picker wired into image-upload fields
- Reusable Blade view at `resources/views/filament/components/media-picker.blade.php`
- Initially built as inline-Alpine `x-data` — broke because (a) injected modal `<script>` doesn't execute, (b) nested quote-mode collisions corrupted the `x-data` attribute
- Fix: registered `Alpine.data('mediaPicker', ...)` in a standalone `public/js/media-picker.js`, injected on every panel page via a Filament `renderHook(panels::head.end, ...)`. Blade view just calls `x-data="mediaPicker(@json($statePath))"` — no inline JS.
- Picker click → calls `$wire.call('pickMediaToState', statePath, path)` on the parent Filament page
- Created `App\Concerns\HandlesMediaPicking` trait providing `pickMediaToState()` — routes the value through `$this->form->fill()` so Filament's `FileUpload` hydration runs cleanly (direct `$this->data[key] = path` caused "foreach() on string" in `BaseFileUpload`)
- Trait used in: `Settings`, `EditProduct`, `CreateProduct`
- Hardened Settings hero section: prominent "Pick hero image from media library" + "Clear hero" action buttons, separate from the FileUpload (which is now labeled "Or upload a new custom hero image")
- Settings page now shows a live current-hero preview Placeholder above the actions

### Push-based git deploy
- Created `.gitignore` covering: `.env`, `vendor/`, `node_modules/`, `public/build/`, `public/{js,css}/filament/` (regenerated by `filament:upgrade`), `storage/app/public/{products,hero}/*` (uploaded data), `storage/logs/*`, `docs/chat/`, `docs/adminer-credentials.csv`, `docs/WhatsApp*.zip`, `.mcp.json`
- `git init` + first commit (197 files) pushed to <https://github.com/JohnnyNassar/je> via HTTPS + PAT
- Server: generated ed25519 deploy key at `/root/.ssh/joreption_deploy`, added as **read-only** deploy key on the repo, configured `/root/.ssh/config` so `git@github.com` routes through it
- `/var/www/joreption` initialized as a git repo + remote set + `git reset --hard origin/main`; `.env` / `vendor/` / uploaded images preserved (gitignored)
- `git config --global --add safe.directory /var/www/joreption` (root running git in a www-data-owned tree)
- Deploy script at `/usr/local/bin/joreption-deploy.sh`:
  - `git fetch --all && git reset --hard origin/main`
  - composer install --no-dev (skippable: `--skip-composer`)
  - npm ci + run build (skippable: `--skip-npm`)
  - `filament:upgrade` to re-publish JS/CSS
  - migrate --force
  - cache config / routes / views
  - `systemctl reload php8.3-fpm` (clears OPcache — every deploy)
- New workflow: edit locally → `git push` → `ssh root@178.18.244.125 /usr/local/bin/joreption-deploy.sh`

### Documentation
- `docs/development.html` — styled HTML build journal (3-day timeline) — created at start of Day 4
- `docs/FEATURES.md` + `docs/DEVELOPMENT_LOG.md` updated to include Day 4

---

## Day 5 — 2026-05-23 → 2026-05-24 (related products, coupons, staff roles, variations)

Roadmap push after a planning discussion with the owner — five features shipped in sequence. Built while local MariaDB was down, so each step was verified by PHP lint + class-load + Blade compile; the six migrations were then run and smoke-tested live at the end.

### Customer-auth migration fix
- The original `2026_05_20_104319_add_auth_to_customers_table` migration was an **empty stub** — `email` / `password` / `email_verified_at` / `remember_token` only existed on the servers because they were added by hand. Customer login crashed on a fresh DB (`Unknown column 'email'`).
- New idempotent migration `2026_05_23_120000_add_auth_columns_to_customers_table` adds them guarded by `Schema::hasColumn` — no-op where they already exist (prod), correct on a fresh clone.

### Related products
- `CatalogController::show` loads up to 8 active same-category siblings (excluding current); product page shows a **"You may also like"** strip reusing the featured-strip card style.

### Coupons / discount codes
- `coupons` table (code, `percent`|`fixed`, value, min order, usage limit + count, start/expiry, active) + `Coupon` model (case-insensitive lookup, `isRedeemable()`, `meetsMinimum()`, `discountFor()`).
- `orders` gains `discount_total` + `coupon_code` snapshot. `App\Services\CouponService` resolves the session code against the live subtotal — shared by cart + checkout.
- Coupon entry on the **cart** page (so applying never wipes a half-filled checkout form); cart / checkout / confirmation / my-orders all show Subtotal / Discount / Total. `used_count` increments inside the checkout transaction.
- Filament `CouponResource` (CRUD, live %/amount suffix, usage shown as `used / max`). Order screens show the code + discount.

### Staff role (single-seller)
- `users.role` (`admin` | `staff`, existing users default `admin`); `User::isAdmin()` / `isStaff()`.
- `App\Filament\Concerns\AdminOnly` trait gates a resource/page — hides it from staff nav **and** 403s on direct URL. Admin-only: Orders, Customers, Coupons, Settings, Staff. Both roles: Products, Categories, Media, Quick Add.
- New admin-only `UserResource` ("Staff") to create accounts (name / email / role / password; can't delete your own account). **Known gap:** nothing yet prevents demoting the last admin.

### Product variations (simple variant rows)
- `product_variants` (product_id, name, stock, nullable `price` + `image_path` overrides, position); `ProductVariant` with `effectivePrice()` / `effectiveImagePath()`. `order_items` gains `variant_id` + `variant_name` snapshot.
- `Product::syncStockFromVariants()` keeps `products.stock` as the **sum of variant stock** (fired on variant save/delete via model events, `saveQuietly` to avoid loops) — so every existing stock query / scope / badge keeps working.
- Detail page branches: variant products get an Alpine selector that live-swaps price / stock / image and the hidden `variant_id`; non-variant products keep the original markup.
- Cart refactored: each line keyed by **product + variant** (`{pid}` or `{pid}-{vid}`); update/remove now take the line key. Variant label + image flow through cart / checkout / confirmation / my-orders. Checkout snapshots the variant name and decrements **variant** stock.
- Filament Product form: collapsible **Variations** repeater (relationship-bound, drag-reorder via `position`). Media library now counts variant images as in-use (protected from orphan deletion).

### Cart fixes
- **`+` / `−` race:** the steppers set `qty` via Alpine then called `form.submit()` synchronously — before `x-model` flushed the new value into the input, so the form posted the *old* quantity ("nothing happens"). Fixed by deferring the submit to `$nextTick`.
- **Inline variant add:** on the cart page, a line whose product has more variants now shows an **"Add another option"** row of quick-add buttons for the other in-stock, not-yet-in-cart variants (rendered once per product).

### Migrations + live verification
- Six new migrations run locally. Smoke test confirmed: `customers` has `email`; the owner's `users.role` = `admin`; coupon math (10% of 200 = 20; fixed 5 capped at subtotal 3 = 3); variant stock roll-up (3 + 2 → 5). Deployed to production on Day 6 (guarded migrations are safe re-runs; `coupons` + `product_variants` create new tables).

---

## Day 6 — 2026-05-24 (category UX, theming, loyalty / points)

Polish plus a new pillar feature — all shipped to production the same day.

### Category UX (the "categories aren't connected" report)
- Root cause was data, not wiring: 44 products with **0** assigned to a category, and the product form's category picker had **no preload** (looked empty until you typed).
- Re-enabled `->preload()` on the category Select (fine for a single-seller shop's small category set) and added a **"Set category" bulk action** on the Products table to assign many products at once (blank option clears it).

### Theming — orange → brand teal
- Storefront: recolored the Featured-strip icon and the "X left" low-stock badges from amber to the brand palette. Order-status pills and admin warnings left as-is (semantic).
- Admin: Filament's default primary was `Color::Amber` (the "orange backend"). Set it to the brand teal via `->colors(['primary' => Color::hex('#287d88')])`. Filament applies panel colors as runtime CSS variables — no asset rebuild needed.

### Loyalty / points ("JorEption Points")
- **Schema:** `loyalty_transactions` ledger (customer, order, points ±, type earn/redeem/adjust, description); `customers.points_balance`; `orders.points_earned` + `points_redeemed`.
- **`App\Services\LoyaltyService`:** admin-configurable earn rate (points per currency) + redeem value (currency per point) + min-redeem; `pointsForAmount()`, `maxRedeemable()` (capped to the order), and idempotent `awardForOrder()`.
- **Earning** is credited when an order becomes **delivered** (Order `updated` model event → `awardForOrder`), guarded by `points_earned` so re-saves never double-credit.
- **Redeeming** is opt-in at checkout for logged-in customers: a "Redeem N points — Save X" checkbox; the summary total updates live (Alpine), the discount **stacks with a coupon** (capped at the order amount), deducted + ledgered inside the checkout transaction.
- **Customer-facing:** points balance card on My Orders; "you'll earn X points" hint at checkout.
- **Dedicated Loyalty admin section** (its own `navigationGroup('Loyalty')`, not buried in Settings):
  - **Points activity** — read-only `LoyaltyTransactionResource` ledger (who / points / type / order, filter by type) — the monitoring view.
  - **Settings** — `LoyaltySettings` page (enable + rates), moved out of the general Settings page so Loyalty is self-contained.
  - Per-customer **points** column + manual **"Adjust points"** action on Customers (writes a ledger entry); points columns on Orders.
  - Structured to grow: reporting, follow-ups, and promotions become additional pages in this same group.
- **Verified** by a rolled-back smoke test: earn 50 on a delivered 50.00 order; redemption capping (300 pts against a 3.00 window); idempotent re-deliver.

### Admin dashboard + UI density
- New **admin dashboard** (admin-only widgets, gated via `canView`): a stats row (orders + pending, delivered revenue, customers, low-stock), a 14-day orders line chart in brand teal, and a clickable "Latest orders" table. Dropped Filament's promo info widget.
- **Density pass** (the storefront "felt empty"): shared layout widened `max-w-7xl` → `max-w-screen-2xl`, catalog gains a 5th column at `xl`, the hero shrank from `aspect-[21/9]` (~650px) to ~224px, and section margins / grid gaps tightened. Admin panel set to `maxContentWidth(MaxWidth::Full)` to drop Filament's ~1280px cap. (New Tailwind utilities mean assets must be rebuilt — the deploy's `npm run build` handles prod.)

### Notification channels (WhatsApp / SMS / Email / Dashboard)
- New admin **Notifications** page (`NotificationSettings`, admin-only) to configure each channel: in-app (Dashboard) alerts + recipient, Email (SMTP host/port/user/pass/from), SMS (provider + key/secret/from), and WhatsApp Cloud API (phone number ID + access token). Stored in the `settings` table; secrets use password inputs.
- **In-app channel works end-to-end:** enabled Filament database notifications (topbar bell, 30s polling) + a `notifications` table (`char(36)` id for MariaDB-version portability); a new order fires an in-app alert to admins via the Order `created` event → `sendToDatabase`, gated by a toggle (default on), wrapped in try/catch so it can never break checkout.
- Email / SMS / WhatsApp *sending* is the per-channel follow-up (needs the client's provider credentials); this ships the **configuration UI** now.
- **Queue must be sync.** Filament's DB notifications are queued and this shop runs no queue worker, so `QUEUE_CONNECTION=sync` (else alerts — and later password-reset emails — sit unprocessed in the `jobs` table). `.env` change, applied on prod too.

### Email wiring + in-app Help guide
- **Email is driven by the Notifications UI:** `AppServiceProvider::configureMailFromSettings()` applies the saved SMTP settings (host/port/user/pass/encryption/from) over Laravel's mail config at runtime when Email is enabled — no `.env` editing. Added a **"Send test email"** header action to verify creds. Once the owner pastes relay credentials (Resend/Brevo recommended — not the VPS's own mail, for deliverability), password-reset emails send. The override runs **before** the subpath early-return in `boot()`, so it also applies on prod.
- **Help page:** new admin **Help** page (`/admin` → Help) — a full plain-language user guide covering every area (products, variations, Quick Add, categories, orders, customers, coupons, loyalty, media, notifications, settings, storefront). Visible to admin + staff.

### Notes worth remembering
- **Filament's default primary is Amber.** To theme the admin, set `panel->colors(['primary' => Color::hex(...)])` — runtime CSS, so `config:clear` + refresh is enough (php-fpm reload on deploy); no `npm run build`.
- **A "management section" wants its own nav entry.** Folding loyalty config into the general Settings page was technically fine but didn't match the ask — a dedicated `navigationGroup` reads as a real section and leaves room for reporting/promotions.
- **Local OPcache was a red herring.** This box has `zend_extension=opcache` commented out, so new code runs immediately; "not showing" was loyalty living inside Settings + not being deployed yet.

---

## Day 7 — 2026-05-24 (loyalty reporting & promotions; email verified)

The Loyalty section gained the reporting + promotions it was structured for, and transactional email was confirmed working end-to-end.

### Email — verified live (Resend)
- Domain `joreption.com` verified in **Resend** (SPF / DKIM / DMARC + send MX all propagated). SMTP plugged into the **Notifications → Email** UI; **From** = `noreply@joreption.com`, admin-alert recipient = the owner's inbox. Settings entered on **both** local and prod (separate DBs).
- First "Send test email" reached Resend but not Gmail — cause was the **admin-alert recipient being blank**, so the test fell back to sending *to* the From address (`noreply@`, which has no mailbox). Setting a real recipient fixed it; the test now lands in the inbox, so password-reset + transactional email work.
- Lesson: the test action falls back to the From address when no recipient is set — a blank "admin email" silently sends mail nowhere readable.

### Loyalty reporting (on Points activity)
- Two **header widgets** above the ledger: a **stats overview** (points outstanding, their currency **liability value**, earned / redeemed over 30 days) and a **6-month bar chart** (earned vs redeemed, brand teal vs red).
- Placed under `app/Filament/Resources/LoyaltyTransactionResource/Widgets/` and referenced via the List page's `getHeaderWidgets()` — deliberately **not** in `app/Filament/Widgets/`, so panel auto-discovery doesn't also drop them on the main dashboard.

### Loyalty promotions (new section)
- **Schema:** `loyalty_promotions` (name, type `multiplier|bonus`, multiplier, bonus_points, min_order_total, starts_at, ends_at, active).
- **Model:** `scopeActive($at)` (enabled + inside date window), `appliesTo($total)` (min-order gate), `apply($base)` (multiplier → `floor(base × n)`, bonus → `base + n`), and a `running` accessor.
- **Resource:** full-CRUD `LoyaltyPromotionResource` under the Loyalty group (nav order now Points activity → Promotions → Settings). Type-aware form (multiplier vs bonus fields toggle via `live()`); table shows the reward, the date window, and a **Running / Scheduled / Ended / Off** status badge + inline on/off toggle.
- **Earning integration:** `LoyaltyService::applyPromotions($base, $total)` picks the **single best** active + applicable promo (max resulting points); `awardForOrder()` uses it and records the promo name in the ledger entry (e.g. *"Order #5 delivered — Double points"*). `estimatedPointsForAmount()` feeds the checkout "you'll earn X" preview so customers see the boost.
- **Verified** (rolled back): a ×2 promo on a delivered 50.00 order → 100 points credited + balance + ledger line naming the promo; min-order gating (50 → no, 150 → yes); apply math (×2→100, +30→80); all nine new classes autoload.

### Notes worth remembering
- **Page-scoped widgets:** to show stat/chart widgets on one page but keep them off the dashboard, put them outside `app/Filament/Widgets/` (so `discoverWidgets` skips them) and reference them from the page's `getHeaderWidgets()`.
- **Redeem points are stored negative** (`type=redeem`, `points = -n`); reporting sums use `abs()` for the redeemed total.
- **Who participates:** every order is attached to a customer (their account, or a `firstOrCreate`-by-phone record for guests), so points are *earned* on delivery for everyone — but *redeeming* requires a logged-in account (`auth('customer')`). A guest builds a balance against their phone record and can spend it once they sign in (the phone-merge links their prior orders).

---

## Day 8 — 2026-05-25 (coming-soon check, visitor analytics, audit log)

### Coming Soon — working as designed
- Reported "not working": the owner toggled it on but still saw the store. Root cause is intentional — the `ComingSoonMode` middleware lets admins through (`auth()->check()` / `admin*` / `livewire*`), so a logged-in admin always sees the real site. Verified the **public** view with an anonymous fetch of the live site (returned the Coming Soon page) and confirmed `coming_soon_enabled='true'` directly in the prod DB. Nothing to fix; preview in an incognito window.

### Visitor analytics (Google Analytics GA4)
- New **Settings → Analytics** field (`google_analytics_id`). A shared partial `resources/views/partials/analytics.blade.php` renders the GA4 `gtag.js` snippet only when the ID is set, and is `@include`d in both the storefront layout and the Coming Soon page (captures pre-launch interest too). The admin panel is deliberately not tracked. The ID is entered per-environment (paste it into the **live** admin, like the email settings).

### Audit / activity log (built-in, not Spatie)
- Composer isn't available on the dev machine and a new runtime dependency + `composer.lock` churn wasn't worth it, so this is a lightweight in-house build with the same "full" capability.
- **Schema:** `activity_logs` (log_name, event, description, subject morph, causer morph, properties JSON, timestamps).
- **`App\Concerns\LogsActivity` trait** on Product / Order / Coupon / LoyaltyPromotion / Category / Setting / User: hooks `created` / `updated` / `deleted` and records before→after (`getChanges` / `getOriginal`) — **but only when an authenticated web-guard user (admin/staff) did it**, so storefront/customer writes and console scripts don't pollute the log. `password` / `remember_token` are excluded; models can override `activityDescription()` and `tweakActivityProperties()` (Setting redacts secrets like the mail password / API keys and labels the entry with the setting key).
- **Auth events:** `Login` / `Logout` / `Failed` listeners in `AppServiceProvider` (registered **before** the subpath early-return so they run on prod) log admin/staff sign-ins (web guard only), wrapped in try/catch.
- **Viewer:** read-only `ActivityLogResource` under a new **System** nav group (admin-only) — who / event / item / action / changes, filterable by event + type.
- **Verified** (rolled back): no-auth write → not logged; admin login logged; admin create → 1 entry with causer; update captured 15→25; `mail_password` stored as `••••••`; GA4 partial renders the tag only when the ID is set.

### Privacy policy + cookie notice
- Bilingual **`/privacy`** page (`Route::view` → `legal.privacy`, EN/AR via locale) covering order data, accounts, cookies, Google Analytics, sharing, retention and contact (WhatsApp). Linked in the storefront footer and **exempted from the Coming Soon middleware** so it's reachable pre-launch.
- A dismissible **cookie notice** (`partials/cookie-notice.blade.php`) — plain JS (no Alpine, so it also works on the Coming Soon page, which doesn't load app.js), choice stored in `localStorage`, links to the policy. Included in both the shop layout and the Coming Soon page. Informational model (GA loads regardless) — appropriate for Jordan; can be upgraded to consent-gated later.

### Notes worth remembering
- **Audit causer gating.** Keying activity logging on `auth('web')->user()` turns it from a write firehose into a clean "who-did-what in the admin" trail — storefront stock decrements and customer order creation are intentionally excluded.
- **Per-environment settings.** Like email, the GA4 ID and Coming Soon toggle live in each environment's DB — set them on the **live** admin, not local.
- **Roadmap reviewed (2026-05-25):** next priorities ordered as launch-readiness → SEO / Search Console → outbound WhatsApp/SMS sending → more features (see the Roadmap in `FEATURES.md`).

---

## Day 9 — 2026-05-25 → 2026-05-26 (admin role tiers, customer tiers & tier pricing, richer audit log)

### Admin role tiers (super admin / admin / staff)
- Was two-tier (`admin` = full, `staff` = catalog). Now three: **`super_admin`** (owner), **`admin`** (everyday ops — orders, customers, coupons, loyalty, catalog), **`staff`** (catalog only).
- New `SuperAdminOnly` trait (mirrors `AdminOnly`) gates the owner-only areas: Staff/user management, the three Settings pages (Store / Notifications / Loyalty) and the Activity log. `User::isAdmin()` was redefined to mean "admin **or** super_admin" so every existing `AdminOnly` gate and dashboard widget keeps working for both tiers; `isSuperAdmin()` is the new strict check.
- Idempotent migration promotes existing `admin` rows → `super_admin` so the owner keeps full access. Staff manager role dropdown + badge updated to three roles; gating the Staff resource to super-admin-only also closes the privilege-escalation path (an `admin` can't mint a `super_admin`).
- Fixed a regression from the rename: the new-order admin notification queried `role = 'admin'` — now `whereIn(['admin','super_admin'])` so the owner still gets alerts.

### Customer tiers + tier pricing
- `customers.tier` (regular | vip | wholesale), default regular, set in the Customers admin (badge + filter). `Customer::TIERS` is the single source of truth.
- **`CustomerTierService`** centralises the perks: wholesale customers pay a configurable % off every unit price (applied in `Cart::items()`, so it flows cart → checkout → order line items); VIP customers earn a configurable loyalty-points multiplier (applied in `LoyaltyService`, before the best promotion). Rates are admin settings (`tier_wholesale_discount_percent` on Store settings, `tier_vip_points_multiplier` on Loyalty settings; defaults 10% / ×2).

### Richer activity log
- New `activity_logs.ip_address` + `user_agent`, auto-stamped on every entry by the model's `creating` hook (skipped for console/queue writes with no client IP).
- **Storefront order placements** now logged explicitly in `CheckoutController` (event `placed`, customer or guest) — the trait skips them since no admin is behind the write.
- **Customer storefront logins/logouts** now logged (the `customer` guard listeners in `AppServiceProvider`, type `customer-auth`); customer *failed* logins deliberately not logged to avoid public-storefront bot noise.
- The Activity log resource is now **super-admin-only** (moved with the role change) and gained IP + Device columns and a `placed` event filter.

### Production rollout
- Three surgical deploys (tar+scp the changed files, `migrate --force`, rebuild caches, reload php-fpm), each with a DB snapshot + file-rollback tarball in `/var/backups/joreption/`. Commits `db6fd7b` (roles + tiers), `0aded33` (order logging + IP/UA), `8482cf9` (customer login logging).
- Created the live **`admin@joreption.com`** (admin tier); owner `admin@joreption.local` promoted to super_admin. Set strong passwords on both.
- **End-to-end test on prod** (rolled-back transaction, `Mail::fake()` → zero residue): wholesale `39 → 35.10`, cart/order totals, stock decrement, placement log + IP, VIP `100 → 200` points — 8/8 passed. **Finding: `loyalty_enabled` is off on prod**, so the points program (and the VIP multiplier) is dormant until enabled; wholesale pricing is live and active.

### Notes worth remembering
- **Two separate role systems.** Admin `User.role` (super_admin/admin/staff, `web` guard) is distinct from storefront `Customer.tier` (regular/vip/wholesale, `customer` guard) — "customer" is **not** a `User` role.
- **`isAdmin()` semantics.** Redefining `isAdmin()` as "admin-or-super_admin" let the tier split land with zero edits to the many existing `AdminOnly` / widget call sites; only the genuinely owner-only screens got the new `SuperAdminOnly` trait.
- **Tier perks are inert until used.** Wholesale % and VIP × default on (10 / ×2) but affect nobody until a customer is tagged — safe to deploy live.
- **Safe prod E2E test.** A rolled-back `DB::transaction` + `Mail::fake()` lets you exercise the real cart/order/loyalty code on production with zero residue and no accidental customer emails — far safer than fighting CSRF + Coming Soon over HTTP. (Watch out: a `WHERE name LIKE 'TEST%'` cleanup check matched a pre-existing day-1 `Test Customer`, not residue — confirm by the rolled-back row count, not the name match.)

---

## Day 10 — 2026-06-11 (product cost price & profit, per-user cost access, UI density pass)

### Cost price & profit margin
- `products.cost_price` (nullable decimal) — what the owner paid per unit. `Product` gains a `profit` (price − cost) and `margin_percentage` accessor; both return null when no cost is recorded.
- Shown in the product form and Quick Add, plus toggleable **Cost** + **Profit** columns on the Products table (Profit renders `amount (margin %)`, green for profit / red for a loss). **Never exposed on the storefront** — only admin surfaces reference it.
- Cost changes flow through the existing activity log (an admin write, not a secret — no redaction needed).

### Per-user cost access
- `users.can_view_cost` (boolean, default false). New `User::canViewCost()` = `isAdmin() || can_view_cost` — admins/super-admins always see cost; a single **Staff** member can be granted access without being promoted to the admin tier.
- New **"Can view cost prices & profit"** toggle on the Staff form. All three cost surfaces (form field, table columns, Quick Add — including **server-side rejection** of any cost posted by a user without access) gate on `canViewCost()`.

### UI density pass (admin + storefront)
- Build-free CSS density layer with one font-scale knob per surface: `public/css/admin-density.css` (injected via an `AdminPanelProvider` `renderHook`, root `90%`) and `public/css/storefront-density.css` (linked in the shop layout after `@vite`, root `94%`). Because Filament and Tailwind size in rem, scaling the root font-size shrinks fonts **and** spacing together.
- Targeted Filament trims found by inspecting the **live DOM** (a headless-browser computed-style walk), not by guessing class names: the real row padding lives on the inner `.fi-ta-text` (`py-4`), not `.fi-ta-cell`; the page content wrapper's `py-8` + `gap-y-8` was the big empty band before the table; the topbar's fixed `h-16` was overridden. Result on the products list — rows **60px → 36px**, table top **199px → 174px**, thumbnails **40px → 28px** (`ImageColumn::size(28)`).
- Form density (added after the first deploy): `.fi-fo-component-ctn` field gap **22px → 11px**, field label/input gap and section padding tightened.

### Production rollout
- **Deploy 1 — commit `ac09295`** (cost price + per-user access + admin/storefront density), via the `git push` → `joreption-deploy.sh` pipeline. **That push also synced the Day 9 commits onto GitHub** — they had only reached prod via tar/scp, so the remote was behind; git and prod are now properly in sync. Pre-deploy DB snapshot at `/var/backups/joreption/predeploy-costprice-*.sql`. Verified live: `products.cost_price` + `users.can_view_cost` columns present, home + admin-login `200`.
- **Deploy 2 — commit `7725d20`** (the **form-density** pass + these doc updates), no schema change. Verified the HTTPS-served `admin-density.css` carries the form rules. **All three — cost price, admin/storefront density, and form density — are live.**

### Notes worth remembering
- **Find Filament's real spacing element by inspecting the DOM — don't guess.** Row height came from `.fi-ta-text`'s `py-4` and the page wrapper's `py-8` / `gap-y-8`, not the obvious `.fi-ta-cell`. One computed-style walk in a headless browser found every contributor; blind CSS guesses had no-opped.
- **Density without a build step.** A plain CSS file scoped per surface (admin via `renderHook`, storefront via a `<link>` after `@vite`) plus a single `:root { font-size }` knob proportionally compacts everything and deploys as an ordinary file — no Vite/theme rebuild.
- **One gate method keeps cost surfaces in sync.** `canViewCost()` mirrors the `isAdmin()` pattern so the form field, table columns, and controller all share one rule; a per-user boolean grants exceptions without inventing a new role.

---

## Day 11 — 2026-06-13 (first real staff member + onboarding guide)

### Staff onboarding
- Created the first non-owner team member, **Jasmine** — account exists on **both local and production** (`users.id = 3` in each).
  - Email: `Yasmine.badr92i@gmail.com` · role **staff** · `can_view_cost = false` — so she can build the catalog but never sees or submits cost price / profit (cost is gated by `canViewCost()` = `isAdmin() || can_view_cost`, so staff-with-flag-off is the only configuration that hides it).
  - Password lives in `joreption.txt` (gitignored from commits — **never** record live passwords here). Initial password had symbols that caused a "credentials do not match" typo loop; reset to a symbol-free password and verified via `Hash::check` on both envs. Jasmine should change it via avatar → Profile.
- New bilingual **Getting Started** Filament page (`app/Filament/Pages/GettingStarted.php` + `filament/pages/getting-started.blade.php`): EN ⇄ AR toggle (choice persisted in `localStorage`), Arabic renders full RTL, numbered step cards covering sign-in/password, adding a product, variations, categories, media and tips. Sits at `navigationSort = -1`, just under the Dashboard.
- **Quick Add is now admin-only.** `QuickAddController::show()`/`store()` `abort_unless(isAdmin(), 403)`; the step was also dropped from the staff guide. (Its `store()` had accepted `cost_price`, so gating it also closes a cost-submission path for staff.)
- Shipped in commit `70cf614`, deployed to prod via the `git push` → `joreption-deploy.sh` pipeline (no migration). Verified live: Jasmine logs in, Getting Started renders EN/AR, `/admin/quick-add` returns 403 for her.

### Notes worth remembering
- **Cost visibility can't be granted to admins selectively** — `canViewCost()` short-circuits to true for any admin. To withhold cost from a person, they must be `staff` with the flag off; there is no "admin who can't see cost".
- **Two distinct tracking systems — don't confuse them.** (1) The **audit/activity log is ours**, built in-app (Day 8): the `App\Concerns\LogsActivity` trait records admin/staff create/update/delete (who + before/after + IP + UA) to the `activity_logs` table, and auth listeners in `AppServiceProvider` log admin login/logout/**failed_login** (failed records the typed email + IP) and customer login/logout. Viewable at **System → Activity log** (super-admin only). It logs *actions and auth events, not page navigation*. (2) **Google Analytics (GA4)** is a separate third-party snippet (`resources/views/partials/analytics.blade.php`, toggled from Settings) that tracks anonymous **storefront** pageviews only — it sees nothing in the admin and identifies no individual admin user. So "who logged in" came from our own audit log, not GA.

---

## Lessons learned (worth remembering)

- **OPcache vs deploys.** PHP-FPM had `opcache.validate_timestamps=0` somewhere in its config, so simply replacing PHP files left old bytecode in memory and made my fixes look like they had no effect. **All deploys now `systemctl reload php8.3-fpm`** as the last step.
- **bash + bcrypt.** Passing a bcrypt hash through a bash variable into a `mariadb -e "UPDATE ... password=\"$HASH\""` mangles the `$` characters of the hash. Fix: do the hashing and the UPDATE inside a single PHP script (or use Eloquent's `hashed` cast).
- **Subdirectory + Livewire.** Running Laravel from a sub-path (`http://localhost/joreption`) confuses Livewire's default URL detection. Solution: override `Livewire::setUpdateRoute()` / `setScriptRoute()` in `AppServiceProvider` — but only when the path prefix is non-empty (don't apply on production where there's no subpath).
- **Filament + production.** Filament 3 defaults to denying all panel access when `APP_ENV !== 'local'`. The `User` model must implement `FilamentUser::canAccessPanel()` to allow login.
- **Static asset regex + virtual files.** Nginx regex blocks like `location ~* \.(css|js|jpg|...)$` will try to serve them as static files and 404 when they don't exist on disk. Add `try_files $uri /index.php?$query_string;` to fall through to Laravel for virtual assets (Livewire's `/livewire/livewire.js`, etc.).
- **First-connect SSH from Windows.** OpenSSH on Windows doesn't have `sshpass`. Use PuTTY's `plink -pwfile ... -hostkey "SHA256:..."` to bootstrap key-only SSH from a password-only state.
- **WhatsApp export is messy.** `<Media omitted>` outnumbers actual file references; product names are buried in mixed Arabic+English ad-copy; prices use multiple syntaxes (`*السعر X دينار*`, `*سعر العرض X*`, `Online X$` as reference); admin promo lines like `25JD` end up as product "names" without manual review. Plan on importing as drafts, not active.
- **Pricing dialect matters.** "السعر" is the actual selling price; "سعره بالسوق" is the market reference price (skip); "Online $X" is a reference price (skip). Got bitten by accepting any number followed by `دينار` as the price — fixed by ordering regex patterns from most-specific to least.
- **Filament modal `<script>` tags don't execute.** When Filament injects modal content into the DOM via `innerHTML`, inline `<script>` blocks inside the injected HTML are silently skipped (browser security rule). Workaround: register Alpine components globally via a standalone JS file loaded on every panel page (Filament `renderHook(panels::head.end, ...)` → `<script src="...">`).
- **HTML attribute quote modes collide with JS string quotes.** Putting nested `"` strings inside `x-data="..."` (or vice versa) corrupts the attribute. Tailwind/Alpine docs say "use the opposite quote on the outside" but with `@js()` outputting double-quoted JSON, it's a constant battle. Lesson: don't write multi-line JS inline in HTML — use `Alpine.data()` registrations.
- **Filament `FileUpload` state ≠ plain string.** Setting `$this->data['image_path'] = 'foo.jpg'` directly causes `BaseFileUpload` to crash with "foreach() argument must be of type array, string given" when the form re-renders. Fix: route updates through `$this->form->fill($merged)` so the component's hydration runs.
- **`composer dump-autoload` is needed for new classes on prod.** Adding a new `app/Concerns/Foo.php` and rsyncing it isn't enough — Composer's classmap doesn't know about it until `composer dump-autoload` runs. Symptom: `Method Foo::bar not found` from $wire.call.
- **`git init` inside a www-data tree as root.** Triggers "dubious ownership" since git 2.36. Fix: `git config --global --add safe.directory /var/www/joreption`.
- **`set -e` + `git remote remove` (missing remote) aborts the script.** Use `git remote remove origin 2>/dev/null || true` to keep going when there's nothing to remove.
- **`git reset --hard origin/main` is safe for our prod sync.** It removes tracked-but-modified files, but leaves **untracked + gitignored** files alone. So `.env`, `vendor/`, `node_modules/`, uploaded images all survive a hard reset, as long as they're in `.gitignore`.
- **Filament published assets disappear on reset.** `/public/{js,css}/filament/` are tracked-then-removed if you gitignore them. Mitigation: run `php artisan filament:upgrade` as part of every deploy — it re-publishes them.
- **Alpine `x-model` + immediate `form.submit()` race.** Setting a bound property then calling native `form.submit()` on the same synchronous line submits the *old* input value — `x-model` flushes to the DOM on a microtask, after the submit. Wrap the submit in `$nextTick(() => …)`. (This is why the cart `+`/`−` "did nothing".)
- **Editing an already-run migration doesn't re-run it.** A migration recorded in the `migrations` table won't re-execute on `migrate`. To fix a bad/empty one across environments, add a **new** idempotent migration (`Schema::hasColumn` guards) rather than editing the old file in place.
- **Trait-based `canAccess()` gates Filament cleanly.** Overriding `public static function canAccess(): bool` on a Resource or custom Page both removes it from navigation and 403s on direct URL access — one trait (`AdminOnly`) covers both. Verify trait composition by force-loading the class (`class_exists`) since `php -l` won't catch a trait-method collision.
- **Derived columns via model events.** Keeping `products.stock` as the sum of variant stock is done in `ProductVariant`'s `saved`/`deleted` events calling `Product::syncStockFromVariants()` with `saveQuietly()` — avoids re-firing the parent's events and keeps every existing stock query working without touching them.
- **Filament Repeater `->relationship()`** auto-creates/updates/deletes child rows on parent save and fires each child's model events, so the stock roll-up above just works from the admin form too.
