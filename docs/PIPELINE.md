# Joreption — Pipeline (proposed work, pending client decisions)

Proposed/explored work that is **not yet started** and is **awaiting client sign-off**. Detailed feature catalogue of what already exists lives in `FEATURES.md`; build history in `DEVELOPMENT_LOG.md`. This file is the shortlist to review with the client.

> A tabbed visual version of the overall status (delivered / inquiries / pipeline) lives at **`docs/project-status.html`**. It is tracked, but deliberately **outside `public/`** so it is *not* served on joreption.com — it spells out the security debt, and that must not be a page on the live site. Open it as a local file, or send the file itself to the client. **If you move it back into `public/`, it becomes a public URL.**

_Last updated: 2026-07-18._

**Status legend:** 🟡 proposed / awaiting decision · 🔵 scoped, ready to build · ⏸️ deferred (later) · ✅ shipped

> **Shipped since the last revision — JorEption Bazar table booking (2026-07-18).** Not previously in this pipeline: the client surfaced the venue floor plan for the physical bazaar at 5th Circle and it was built and deployed the same day. **Live and public at <https://joreption.com/bazar>**, even though the shop itself is still behind the Coming Soon splash. 30 nights (Thu/Fri, 23 Jul – 30 Oct 2026), 100 tables, 88 rentable at 30 JD/night, an interactive floor plan generated from the database, and a database-level double-booking guard. See §4 for the open decisions and `DEVELOPMENT_LOG.md` Day 21 for detail.

---

## 1. 🟡 Product videos

**Goal:** let the client add videos to products. Client leans toward a **mix of short clips and longer videos**, shown as **muted autoplay loops**.

### Key design point — treat video as two roles, not one
Muted autoplay-loop fits a **short clip** (behaves like a GIF / extra photo), but is wrong for a **long video** (wastes mobile data + bandwidth, audio-less). So:

| | Short clip | Long video |
|---|---|---|
| **UX** | muted autoplay loop, as a slide in the image gallery | poster thumbnail + tap-to-play with sound |
| **Hosting** | self-host on the VPS (small files) | self-host if low volume, else managed CDN |
| **Main concern** | minimal | **bandwidth** |

### The real constraint is bandwidth, not storage
Server has **140 GB free** (only 42 MB used today), so storing video is a non-issue. The limit is **serving** it: every view streams from the single Contabo pipe with **no adaptive bitrate**. Short muted loops are tiny → fine self-hosted. Long videos at real traffic can saturate the pipe → that's what a managed video CDN solves.

### Recommended plan
- **Phase 1 — self-host both, split by role:** ffmpeg-transcode to H.264/AAC MP4, generate poster frames. Short clips render `<video muted autoplay loop playsinline preload="none">` that **only plays when scrolled into view** (IntersectionObserver). Long videos render poster + tap-to-play. No new monthly cost.
- **Phase 2 — only if long-video traffic grows:** move long videos to **Bunny Stream** (adaptive HLS + CDN, ~pennies/GB) or Cloudflare Stream ($5 per 1000 min stored + $1 per 1000 min delivered). Data model stores a *source type*, so swapping self-host → CDN is a URL change, not a rebuild.

### Build steps (when approved)
- **Server prep:** install **ffmpeg** (not present today); raise php-fpm `upload_max_filesize`/`post_max_size` and nginx `client_max_body_size` (e.g. 64 MB; currently 2 MB / 8 MB / 32 MB); bump `fastcgi_read_timeout`; enable **chunked uploads** so large files don't fail mid-upload.
- **Data model:** lightweight `videos` JSON array on the product (mirrors existing `gallery`), each entry `{ src/url, source: upload|bunny|youtube, type: clip|long, poster }` — supports self-host *and* a future CDN/embed with no schema change.
- **Admin (Filament):** video upload field gated to the image-tool roles (assume **admin-curated** for now); transcode + poster in a model `saved` event (like the existing `ImageResizer`).
- **Storefront:** the two players above, reusing the new `?v=<mtime>` cache-busting helper; `playsinline` + `muted` for iOS autoplay; `controlsList="nodownload"` to match the image-save deterrent.

### Decisions needed from the client
- Confirm the **mix** (short + long) and the **autoplay-loop for short / tap-to-play for long** split.
- **One video per product, or several?** (JSON-array supports many; v1 is simpler with one.)
- **Clip vs long** marked by an admin toggle, or auto-detected by duration (ffmpeg knows length)?
- Expected **volume** (how many products get a video, who uploads) — drives the Phase 1→2 timing.

**Rough effort:** ~1 day for Phase 1 v1 (server config + ffmpeg pipeline + Filament field + storefront players). CDN offload (Phase 2) is a later, smaller add.

---

## 2. ⏸️ Multi-seller marketplace (separate product — reuse this codebase)

**Idea (deferred — not now):** reuse this codebase as the ~80% foundation for a **separate multi-seller marketplace**, hosted on **another server**. Decisions already set:
- **Do NOT convert joreption.com** — it stays a single-owner store.
- **Marketplace** model (one storefront/brand, many vendors, customers shop across sellers in one cart, platform takes commission) — **not SaaS** (not many isolated stores).
- Reuse the existing catalog, variants, categories, cart/COD checkout, bilingual EN/AR, loyalty, admin panel, image tooling rather than rebuild.

### How to reuse the code (recommended)
- **Single repo with a `PLATFORM_MODE=single|marketplace` env flag** — joreption deploys with it off; the marketplace deploys from the *same* repo with it on. Avoids two diverging codebases / double maintenance. (Alternatives: hard fork, or shared-upstream git remote.)

### Net-new work (the hard 20% code reuse does NOT save)
- **Seller** entity + `products.seller_id`; Filament v3 **multi-tenancy** to scope each seller's products/orders/dashboard.
- **Order-splitting** — one cart → per-seller sub-orders/fulfillments.
- **Commission / payout ledger.**
- Seller onboarding/approval, moderation, per-seller shipping/returns/ratings, tax/invoicing.
- **Per-seller data isolation** is a security surface — tenancy + policies, tested.

### Biggest risk — COD payouts, not the code
No easy Jordan-local split-payment processor, so payouts stay a **manual ledger + bank transfers**. Must decide who collects the cash: platform courier remits to seller minus commission, vs seller collects and owes commission.

**Rough effort:** weeks, not days. Phase it; defer automated split-payments (impractical under COD).

---

## 3. ⏸️ Pre-launch & hygiene (tracked elsewhere — listed for completeness)

These are **not new proposals** but the outstanding items to clear before/around go-live (full detail in `FEATURES.md` Roadmap §1 and the dev log):

- **Launch readiness:** activate imported product drafts; fix mis-entered variants (e.g. product 91 has a single `green` variant → no colour chooser); set the live `admin_whatsapp` number; decide on loyalty; flip Coming Soon off. _(None of this blocks the bazaar, which is already live at `/bazar`.)_
- ⚪ **Security debt — owner decision, 2026-07-19: not rotating.** Raised repeatedly since Day 17 and declined; treated from here as an **accepted risk**, not an open task, and not to be re-raised unprompted. For the record, the position is: secrets remain live and remain in the **public** repo, and root SSH password login stays enabled, while `/bazar` is live and being shared. If the decision changes, the work is to rotate each secret at its provider, then `git rm --cached` + gitignore. The cheaper alternative is making the repo private — prod's pull would move from HTTPS to the deploy key already on the server (`/root/.ssh/joreption_deploy`).
- **Infra:** webhook-triggered auto-deploy (replace the manual SSH git-pull).
- ✅ **Dev environment — fixed 2026-07-19.** The old `D:\Git` install was broken (missing DLLs, every binary exited `0xC0000135`), so the 2026-07-18 bazaar deploy had to be pushed via the GitHub API. Git 2.55.0.3 is now installed at `C:\Program Files\Git`, on PATH, with `gh` wired in as the credential helper (`gh auth setup-git`) — push authenticates and the normal edit → commit → push → SSH-deploy flow works again. The dead `D:\Git` tree is still on disk (~68 MB, not on PATH); remove it with `D:\Git\unins000.exe` if you want it gone.

---

## 4. 🟡 Bazaar — open decisions & follow-ups

The booking system is live; these are the loose ends.

### Decisions needed from the client
- **Are the 12 restaurant units rentable?** Currently **not** (`is_bookable = false`) — they're drawn on the plan for orientation only. That sets capacity at **88 tables/night, not 100** (≈79,200 JD per season at full occupancy vs 90,000). If food vendors do pay for them, they likely pay a different rate — say the number and it's a one-line change.
- **Venue address + Google Maps link.** The page says only "Amman — 5th Circle" because nothing more precise was known; no address or map link was invented. Supply both and they go on the page.
- **Marketing copy.** The page states only verifiable facts (location, both time windows, 30 JD, cash on the night). Nothing about parking, facilities, what may be sold, or rules. Worth the client's own words before it circulates widely.
- **Visual check of the floor plan.** The seeded data is verified against the architect's legend (A 14 · B 12 · C 56 · D 6 · R 12 = 100, zero overlapping rectangles), but the *rendered look* has never been compared to `docs/JorEptionBazar.jpeg` by eye.

### Not built (candidates, roughly by value)
- **Booking lookup by phone** — a vendor who loses the confirmation page has no way back to it (mirrors the existing `/track` for orders). ~2 h.
- **Automatic confirmation message** — approving a booking currently notifies nobody; the admin clicks the WhatsApp action manually. Wiring the existing WhatsApp/SMS config would close the loop.
- **Public vendor directory** — "who's at table 42", turning the plan into something shoppers browse and giving vendors a reason to fill in their shop name. Data is already collected.
- **Per-table QR codes** — physical-to-digital bridge on the night; also the natural onboarding funnel if the marketplace idea (§2) is ever picked up.
- **Recurring bookings** — a vendor wanting the same table every week must book each of the 30 nights separately. Worth doing if regulars emerge.
- **Season reporting** — occupancy and takings across nights; per-night figures exist in the admin, but there's no season view.

---

## 5. 🟡 Minor / quick decisions

- **Cover-logo access for staff ("Yasmine").** The "Cover logo" tool is admin-only (`isAdmin()`). To let a Staff member use it: either **promote her to Administrator** (broad — grants all back-office powers), or add a scoped **`can_cover_logo`** flag mirroring the existing `can_view_cost` pattern (a migration + a `Toggle` in the user form + swapping the controller's two guards to `canCoverLogo()`). **Owner to decide.**
