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

### Notes worth remembering
- **Filament's default primary is Amber.** To theme the admin, set `panel->colors(['primary' => Color::hex(...)])` — runtime CSS, so `config:clear` + refresh is enough (php-fpm reload on deploy); no `npm run build`.
- **A "management section" wants its own nav entry.** Folding loyalty config into the general Settings page was technically fine but didn't match the ask — a dedicated `navigationGroup` reads as a real section and leaves room for reporting/promotions.
- **Local OPcache was a red herring.** This box has `zend_extension=opcache` commented out, so new code runs immediately; "not showing" was loyalty living inside Settings + not being deployed yet.

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
