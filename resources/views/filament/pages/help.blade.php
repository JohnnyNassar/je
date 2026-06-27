<x-filament-panels::page>
    @php
        $sections = [
            'getting-started' => 'Getting started',
            'dashboard' => 'Dashboard',
            'products' => 'Products',
            'variations' => 'Product variations',
            'quick-add' => 'Quick Add (mobile)',
            'categories' => 'Categories',
            'orders' => 'Orders',
            'customers' => 'Customers',
            'coupons' => 'Coupons',
            'loyalty' => 'Loyalty points',
            'media' => 'Media library',
            'notifications' => 'Notifications',
            'settings' => 'Settings',
            'storefront' => 'The customer site',
        ];
    @endphp

    <div class="space-y-6 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
        {{-- Intro + contents --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-bold text-gray-950 dark:text-white">Welcome to your Joreption admin</h2>
            <p class="mt-2">This guide explains how to run the store day to day. Jump to a section:</p>
            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($sections as $id => $label)
                    <a href="#{{ $id }}" class="rounded-lg bg-gray-50 px-3 py-2 text-xs font-medium text-primary-700 ring-1 ring-gray-950/5 hover:bg-primary-50 dark:bg-white/5 dark:text-primary-300 dark:ring-white/10">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @php
            $card = 'scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10';
            $h = 'text-base font-bold text-gray-950 dark:text-white';
            $sub = 'mt-4 font-semibold text-gray-900 dark:text-gray-100';
            $ul = 'mt-2 list-disc space-y-1 ps-5';
            $ol = 'mt-2 list-decimal space-y-1 ps-5';
        @endphp

        {{-- Getting started --}}
        <section id="getting-started" class="{{ $card }}">
            <h3 class="{{ $h }}">Getting started</h3>
            <ul class="{{ $ul }}">
                <li><strong>Logging in:</strong> the admin is at <code>/admin</code>. Each staff member has their own account.</li>
                <li><strong>Two roles:</strong> <strong>Admin</strong> sees everything (orders, customers, money, settings). <strong>Staff</strong> can only manage the catalog — Products, Categories, Media, and Quick Add — and never sees orders, customers, coupons, or settings.</li>
                <li><strong>Add staff:</strong> Admins create accounts under <strong>Staff</strong> in the sidebar (name, email, role, password).</li>
                <li><strong>Light / dark mode &amp; your password:</strong> use the avatar menu at the top-right (Profile to change your password).</li>
            </ul>
        </section>

        {{-- Dashboard --}}
        <section id="dashboard" class="{{ $card }}">
            <h3 class="{{ $h }}">Dashboard</h3>
            <p class="mt-2">The home screen shows the pulse of the store:</p>
            <ul class="{{ $ul }}">
                <li><strong>Stats:</strong> total orders (with how many are still pending), revenue from delivered orders, customer count, and a low-stock alert.</li>
                <li><strong>Orders chart:</strong> orders per day over the last two weeks.</li>
                <li><strong>Latest orders:</strong> the most recent orders — click any row to open it.</li>
                <li>The <strong>bell</strong> at the top-right alerts you when a new order comes in.</li>
            </ul>
        </section>

        {{-- Products --}}
        <section id="products" class="{{ $card }}">
            <h3 class="{{ $h }}">Products</h3>
            <ul class="{{ $ul }}">
                <li><strong>Add / edit:</strong> open <strong>Products → New product</strong>. Fill the English and Arabic name and description — the site shows the right language to each visitor automatically.</li>
                <li><strong>Price &amp; stock:</strong> set the selling price and how many you have. When stock hits 0 the item shows "Out of Stock".</li>
                <li><strong>Sale price (Original price):</strong> enter a higher "original price" to show a red <em>"Save X%"</em> badge with the old price crossed out.</li>
                <li><strong>Featured:</strong> turn this on to feature the product in a strip at the top of the home page.</li>
                <li><strong>Category:</strong> pick a category so it shows under that filter on the site (see Categories).</li>
                <li><strong>Image:</strong> upload one, or click <em>"Choose from media library"</em> to reuse an existing image. Images are auto-resized.</li>
                <li><strong>Active:</strong> only active products appear on the site — leave it off to keep a draft hidden.</li>
                <li><strong>Copy link:</strong> the products list has a "Copy link" button to grab a product's public URL for sharing on WhatsApp.</li>
                <li><strong>Bulk category:</strong> tick several products in the list and use <em>"Set category"</em> to assign them all at once.</li>
            </ul>
        </section>

        {{-- Variations --}}
        <section id="variations" class="{{ $card }}">
            <h3 class="{{ $h }}">Product variations (colors / sizes)</h3>
            <ul class="{{ $ul }}">
                <li>On the product form, open the <strong>Variations</strong> section to add options like "Red / M", each with its <strong>own stock</strong> and, optionally, its own price and image.</li>
                <li>When a product has variations, its total stock is calculated automatically from them.</li>
                <li>On the site the customer picks an option before adding to cart; each option is priced and counted separately.</li>
                <li>Leave Variations empty for a simple product with one price and stock.</li>
            </ul>
        </section>

        {{-- Quick Add --}}
        <section id="quick-add" class="{{ $card }}">
            <h3 class="{{ $h }}">Quick Add (from your phone)</h3>
            <ul class="{{ $ul }}">
                <li>Open <code>/admin/quick-add</code> on your phone (you can add it to your home screen like an app).</li>
                <li>Snap a photo (or several), it compresses them automatically, then fill the name, price, and stock and publish.</li>
                <li>There's a voice-input button for the Arabic description, and after publishing you get a "Copy link" button to paste straight into WhatsApp.</li>
            </ul>
        </section>

        {{-- Categories --}}
        <section id="categories" class="{{ $card }}">
            <h3 class="{{ $h }}">Categories</h3>

            <p class="{{ $sub }}">Adding a category — step by step</p>
            <ol class="{{ $ol }}">
                <li>In the sidebar, open <strong>Categories</strong>, then click <strong>New category</strong> (top-right).</li>
                <li><strong>Parent category:</strong> leave it on <em>"— none (top-level category) —"</em> for a main category. To make a sub-category, pick its parent here. (Categories go <strong>2 levels deep</strong> — a sub-category can't itself have sub-categories.)</li>
                <li><strong>Name (English):</strong> required — e.g. "Electronics".</li>
                <li><strong>Name (Arabic):</strong> optional but recommended — the site shows each visitor the right language.</li>
                <li><strong>Slug:</strong> leave blank and it's created automatically from the English name (it's the category's web address). Only set it by hand if you need a specific one.</li>
                <li><strong>Position:</strong> a number that controls order — lower numbers appear first. You can leave it at 0 and just drag to reorder later (see below).</li>
                <li><strong>Active:</strong> leave this on so the category shows on the site. Turn it off to hide it while you set things up.</li>
                <li>Click <strong>Create</strong>. The category now appears in the list.</li>
            </ol>

            <p class="{{ $sub }}">After creating</p>
            <ul class="{{ $ul }}">
                <li><strong>Reorder:</strong> on the Categories list, drag the rows by the handle to change the order they appear on the site (this updates "Position" for you).</li>
                <li><strong>Put products in it:</strong> assign a category on the product form, or tick several in the Products list and use <em>"Set category"</em> to do them in bulk. The list shows a <strong>Products</strong> count for each category.</li>
                <li><strong>Edit / delete:</strong> click a row to edit it; use the bulk actions to delete. A category with no products simply won't show anything on the site until you assign some.</li>
            </ul>
        </section>

        {{-- Orders --}}
        <section id="orders" class="{{ $card }}">
            <h3 class="{{ $h }}">Orders</h3>
            <ul class="{{ $ul }}">
                <li>Every order has a <strong>status</strong>: <em>Pending → Confirmed → Delivered</em> (or Cancelled). Change it from the orders list or the order page.</li>
                <li>Marking an order <strong>Delivered</strong> is important: that's when the customer earns loyalty points (if loyalty is on).</li>
                <li>The sidebar shows a badge with the number of <strong>pending</strong> orders waiting for you.</li>
                <li>Open an order to see the items, the customer's contact and address, any coupon/points discount, and the total.</li>
                <li>Payment is <strong>Cash on Delivery</strong> — you collect when you deliver.</li>
            </ul>
        </section>

        {{-- Customers --}}
        <section id="customers" class="{{ $card }}">
            <h3 class="{{ $h }}">Customers</h3>
            <ul class="{{ $ul }}">
                <li>Anyone who orders is saved here (matched by phone), whether they made an account or checked out as a guest.</li>
                <li>You can see each customer's order count, total spent, and loyalty points balance.</li>
                <li><strong>Adjust points:</strong> use the "Adjust points" action to add or remove points by hand (e.g., a goodwill bonus) — it's recorded in the points history.</li>
            </ul>
        </section>

        {{-- Coupons --}}
        <section id="coupons" class="{{ $card }}">
            <h3 class="{{ $h }}">Coupons</h3>
            <ul class="{{ $ul }}">
                <li>Create a code under <strong>Coupons</strong>: choose <em>percentage</em> (e.g. 10%) or a <em>fixed amount</em> off.</li>
                <li>Optional limits: a minimum order total, a maximum number of uses, and start / expiry dates.</li>
                <li>Customers enter the code in their cart; the discount shows through checkout and on the order.</li>
                <li>The list shows how many times each coupon has been used.</li>
            </ul>
        </section>

        {{-- Loyalty --}}
        <section id="loyalty" class="{{ $card }}">
            <h3 class="{{ $h }}">Loyalty points</h3>
            <ul class="{{ $ul }}">
                <li><strong>Turn it on</strong> under <strong>Loyalty → Settings</strong>, then set the rates: how many points are earned per dinar spent, what each point is worth when redeemed, and the minimum points needed to redeem.</li>
                <li><strong>Earning:</strong> points are credited automatically when an order is marked <em>Delivered</em>.</li>
                <li><strong>Redeeming:</strong> logged-in customers can apply their points at checkout for a discount — it can stack with a coupon.</li>
                <li><strong>Points activity</strong> (under Loyalty) is the full history of every points earned, redeemed, or adjusted.</li>
            </ul>
        </section>

        {{-- Media --}}
        <section id="media" class="{{ $card }}">
            <h3 class="{{ $h }}">Media library</h3>
            <ul class="{{ $ul }}">
                <li>Browse every uploaded image, see which are in use, and find <strong>orphans</strong> (images not used by any product).</li>
                <li>You can copy an image's URL, preview it, and delete orphans to free up space. In-use images are protected from deletion.</li>
            </ul>
        </section>

        {{-- Notifications --}}
        <section id="notifications" class="{{ $card }}">
            <h3 class="{{ $h }}">Notifications</h3>
            <p class="mt-2">Configure how the store alerts you and your customers, under <strong>Notifications</strong>.</p>
            <ul class="{{ $ul }}">
                <li><strong>In-app (Dashboard):</strong> already working — the bell alerts admins on every new order. You can toggle it and set the admin contact email/phone here.</li>
                <li><strong>Email:</strong> enter your email provider's SMTP details (host, port, username, password, from address) and use <em>"Send test email"</em> to confirm it works. This powers password-reset and order emails.</li>
                <li><strong>SMS &amp; WhatsApp:</strong> enter your provider/Cloud-API details here. (Sending is enabled per channel once the account is connected.)</li>
            </ul>
            <p class="{{ $sub }}">Setting up email</p>
            <ul class="{{ $ul }}">
                <li>Create a free account with an email service (Resend or Brevo are recommended), and verify your domain there so mail from <code>&#64;joreption.com</code> lands in inboxes.</li>
                <li>Copy the SMTP host / port / username / password they give you into the Email section, set the "from" name and address, Save, then Send a test email.</li>
            </ul>
        </section>

        {{-- Settings --}}
        <section id="settings" class="{{ $card }}">
            <h3 class="{{ $h }}">Settings</h3>
            <ul class="{{ $ul }}">
                <li><strong>Currency:</strong> code, symbol, and whether the symbol shows before or after the amount.</li>
                <li><strong>Landing page hero:</strong> upload a banner image (with a built-in cropper), pick one from the media library, or feature a product's image.</li>
                <li><strong>Coming Soon mode:</strong> turn this on to show visitors a branded "coming soon" splash while you prepare — admins still see the real site.</li>
                <li><strong>Admin WhatsApp number:</strong> used for the "Contact us on WhatsApp" link on the order confirmation page.</li>
            </ul>
        </section>

        {{-- Storefront --}}
        <section id="storefront" class="{{ $card }}">
            <h3 class="{{ $h }}">What customers see</h3>
            <ul class="{{ $ul }}">
                <li>A bilingual (English / Arabic) shop with search, category filters, a featured strip, and sale badges.</li>
                <li>They add items to a cart, can apply a coupon and redeem points, then check out with Cash on Delivery — no account required.</li>
                <li>They can create an account to see their order history, and track any order at <code>/track</code> with their order number and phone.</li>
            </ul>
        </section>

        <p class="text-center text-xs text-gray-500 dark:text-gray-400">Need a change or run into a problem? Note the page you were on and what you expected, and pass it to your developer.</p>
    </div>
</x-filament-panels::page>
