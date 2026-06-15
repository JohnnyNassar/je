<x-filament-panels::page>
    @php
        // Each step is bilingual; we render the chosen language with an Alpine toggle.
        $steps = [
            [
                'icon' => '🔑',
                'title_en' => 'Sign in & your account',
                'title_ar' => 'تسجيل الدخول وحسابك',
                'items_en' => [
                    'Open the admin at <code>/admin</code> and sign in with your email and password.',
                    'First time here? Change your password: click your avatar (top-right) → <strong>Profile</strong>.',
                    'Switch between light and dark mode from that same avatar menu.',
                ],
                'items_ar' => [
                    'افتحي لوحة التحكم على الرابط <code>/admin</code> وسجّلي الدخول ببريدك الإلكتروني وكلمة المرور.',
                    'أول مرة؟ غيّري كلمة المرور: اضغطي على صورتك (أعلى الزاوية) ← <strong>الملف الشخصي (Profile)</strong>.',
                    'يمكنك التبديل بين الوضع الفاتح والداكن من نفس القائمة.',
                ],
            ],
            [
                'icon' => '🛍️',
                'title_en' => 'Add a product',
                'title_ar' => 'إضافة منتج',
                'items_en' => [
                    'Go to <strong>Products → New product</strong>.',
                    'Type the name and description in <strong>both English and Arabic</strong> — each visitor sees their own language automatically.',
                    'Set the <strong>Price</strong> and the <strong>Stock</strong> (how many you have). When stock reaches 0 the item shows “Out of Stock”.',
                    'Add a photo: upload one, or click <em>“Choose from media library”</em> to reuse an existing image. Images are resized automatically.',
                    'Pick a <strong>Category</strong> so the product appears under the right filter on the site.',
                    'Optional: set a higher <em>“original price”</em> to show a red <em>“Save X%”</em> badge, and turn on <strong>Featured</strong> to highlight it on the home page.',
                    'Leave <strong>Active</strong> off to keep it as a hidden draft; turn it on to publish it to the site. Click <strong>Create</strong> to save.',
                ],
                'items_ar' => [
                    'اذهبي إلى <strong>المنتجات (Products) ← منتج جديد (New product)</strong>.',
                    'اكتبي الاسم والوصف <strong>باللغتين الإنجليزية والعربية</strong> — كل زائر يرى لغته تلقائياً.',
                    'حدّدي <strong>السعر (Price)</strong> و<strong>الكمية المتوفرة (Stock)</strong>. عندما تصل الكمية إلى 0 يظهر المنتج كـ«غير متوفر».',
                    'أضيفي صورة: ارفعي صورة جديدة، أو اضغطي <em>«اختيار من مكتبة الوسائط»</em> لإعادة استخدام صورة موجودة. يتم تصغير الصور تلقائياً.',
                    'اختاري <strong>الفئة (Category)</strong> ليظهر المنتج تحت التصنيف الصحيح في الموقع.',
                    'اختياري: ضعي <em>«السعر الأصلي»</em> أعلى من السعر الحالي لإظهار شارة حمراء <em>«وفّر X%»</em>، وفعّلي <strong>«مميّز» (Featured)</strong> لإبرازه في الصفحة الرئيسية.',
                    'اتركي <strong>«مُفعّل» (Active)</strong> متوقفاً لإبقائه مسودة مخفية، أو فعّليه لنشره في الموقع. ثم اضغطي <strong>إنشاء (Create)</strong> للحفظ.',
                ],
            ],
            [
                'icon' => '🎨',
                'title_en' => 'Product variations (colors / sizes)',
                'title_ar' => 'خيارات المنتج (ألوان / مقاسات)',
                'items_en' => [
                    'For attributes like colour, size or dimension, open the <strong>Options</strong> section and add up to <strong>3 attributes</strong> (e.g. Colour, Size, Dimension), each with its values in English + Arabic (Red/أحمر, M, 50cm…).',
                    'Then open <strong>Variations</strong> and click <em>“Build combinations from options”</em> — it creates one variation per combination automatically (e.g. Red / M / 50cm).',
                    'Fill in each variation’s <strong>stock</strong>, and optionally its own <strong>price</strong> and <strong>photo</strong> — leave price or photo blank to reuse the product’s. Set stock to 0 for combinations you don’t carry.',
                    'On the website customers get a <strong>separate selector per attribute</strong> (Colour, then Size, then Dimension); the photo and price update to match, and unavailable combinations are crossed out automatically.',
                    'You don’t edit the main <strong>Stock</strong> field when there are variations — the total is added up from them automatically.',
                    'For a single attribute (or a quick product) you can skip Options and just add variations by hand in the <strong>Variations</strong> section, naming each one (e.g. “Rectangular”, “Hexagonal”).',
                    'Leave both Options and Variations empty for a simple product with a single price and stock.',
                ],
                'items_ar' => [
                    'للصفات مثل اللون أو المقاس أو الأبعاد، افتحي قسم <strong>الخيارات (Options)</strong> وأضيفي حتى <strong>٣ صفات</strong> (مثل اللون، المقاس، الأبعاد)، ولكل صفة قيمها بالإنجليزية والعربية (Red/أحمر، M، ٥٠سم…).',
                    'ثم افتحي قسم <strong>الخيارات (Variations)</strong> واضغطي <em>«إنشاء التركيبات من الخيارات»</em> — فيُنشئ تلقائياً خياراً لكل تركيبة (مثل أحمر / وسط / ٥٠سم).',
                    'املئي لكل خيار <strong>الكمية</strong>، ويمكن إضافة <strong>سعر</strong> و<strong>صورة</strong> خاصة — اتركيهما فارغين لاستخدام سعر وصورة المنتج. ضعي الكمية 0 للتركيبات غير المتوفرة.',
                    'في الموقع يحصل العميل على <strong>قائمة اختيار لكل صفة</strong> (اللون ثم المقاس ثم الأبعاد)، وتتحدّث الصورة والسعر تلقائياً، والتركيبات غير المتوفرة تظهر مشطوبة.',
                    'لا تعدّلي حقل <strong>الكمية (Stock)</strong> الرئيسي عند وجود خيارات — يُحسب الإجمالي منها تلقائياً.',
                    'لصفة واحدة (أو لمنتج سريع) يمكنك تجاوز قسم الخيارات وإضافة الخيارات يدوياً في قسم <strong>Variations</strong>، بتسمية كلٍّ منها (مثل «مستطيل»، «سداسي»).',
                    'اتركي قسمي الخيارات والتركيبات فارغين للمنتج البسيط ذي السعر والكمية الواحدة.',
                ],
            ],
            [
                'icon' => '🗂️',
                'title_en' => 'Categories',
                'title_ar' => 'الفئات',
                'items_en' => [
                    'Create categories under <strong>Categories</strong> (English + Arabic name). Drag to reorder how they appear on the site.',
                    'Assign a product to a category from the product form, or select several products in the list and use <em>“Set category”</em>.',
                ],
                'items_ar' => [
                    'أنشئي الفئات من قسم <strong>الفئات (Categories)</strong> بالاسم الإنجليزي والعربي. اسحبي لإعادة ترتيب ظهورها في الموقع.',
                    'اربطي المنتج بفئة من صفحة المنتج، أو حدّدي عدة منتجات من القائمة واستخدمي <em>«تعيين فئة» (Set category)</em>.',
                ],
            ],
            [
                'icon' => '🖼️',
                'title_en' => 'Media library',
                'title_ar' => 'مكتبة الوسائط',
                'items_en' => [
                    'Browse every uploaded image and see which ones are in use.',
                    'Reuse an image on a product with <em>“Choose from media library”</em>, copy its URL, or delete unused images (in-use images are protected).',
                ],
                'items_ar' => [
                    'تصفّحي جميع الصور المرفوعة وشاهدي أيها مُستخدم.',
                    'أعيدي استخدام صورة في منتج عبر <em>«اختيار من مكتبة الوسائط»</em>، أو انسخي رابطها، أو احذفي الصور غير المستخدمة (الصور المُستخدمة محمية من الحذف).',
                ],
            ],
            [
                'icon' => '💡',
                'title_en' => 'Handy tips',
                'title_ar' => 'نصائح مفيدة',
                'items_en' => [
                    '<strong>Copy link:</strong> the Products list has a “Copy link” button to grab a product’s public web address for sharing on WhatsApp.',
                    'Always fill <strong>both languages</strong> so Arabic and English customers each see a complete product.',
                    'Use clear, well-lit photos — the first image is the one customers see in the grid.',
                    'Stuck or something looks wrong? Note which page you were on and what you expected, and send it to the owner.',
                ],
                'items_ar' => [
                    '<strong>نسخ الرابط:</strong> في قائمة المنتجات زر «نسخ الرابط» للحصول على رابط المنتج العام ومشاركته على واتساب.',
                    'املئي <strong>اللغتين</strong> دائماً ليرى العملاء بالعربية والإنجليزية منتجاً كاملاً.',
                    'استخدمي صوراً واضحة وبإضاءة جيدة — الصورة الأولى هي التي تظهر للعملاء في القائمة.',
                    'واجهتِ مشكلة أو رأيتِ شيئاً غير صحيح؟ دوّني الصفحة التي كنتِ فيها وما الذي توقعتِه، وأرسليه للمالك.',
                ],
            ],
        ];

        $card = 'rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10';
        $h = 'text-base font-bold text-gray-950 dark:text-white';
        $ul = 'mt-3 list-disc space-y-1.5 ps-5';
        $num = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white';
    @endphp

    <div
        x-data="{ lang: localStorage.getItem('gs_lang') || 'en' }"
        x-init="$watch('lang', v => localStorage.setItem('gs_lang', v))"
        class="space-y-6 text-sm leading-relaxed text-gray-700 dark:text-gray-300"
    >
        {{-- Language toggle + welcome --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                    <span x-show="lang === 'en'">Welcome, Jasmine! 👋</span>
                    <span x-show="lang === 'ar'" dir="rtl">أهلاً ياسمين! 👋</span>
                </h2>
                <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/10">
                    <button type="button" @click="lang = 'en'"
                        :class="lang === 'en' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                        class="px-4 py-1.5 text-sm font-medium transition">English</button>
                    <button type="button" @click="lang = 'ar'"
                        :class="lang === 'ar' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                        class="px-4 py-1.5 text-sm font-medium transition">العربية</button>
                </div>
            </div>

            <div x-show="lang === 'en'" class="mt-3 space-y-2">
                <p>This short guide walks you through everything you'll do day to day — adding products, photos, variations and categories. Use the button above to switch between English and Arabic.</p>
                <p class="text-gray-500 dark:text-gray-400">Your account is for building the catalog — adding and editing products, categories and images.</p>
            </div>
            <div x-show="lang === 'ar'" dir="rtl" class="mt-3 space-y-2 text-end">
                <p>هذا الدليل المختصر يشرح لك كل ما ستقومين به يومياً — إضافة المنتجات والصور والخيارات والفئات. استخدمي الزر بالأعلى للتبديل بين الإنجليزية والعربية.</p>
                <p class="text-gray-500 dark:text-gray-400">حسابك مخصص لبناء الكتالوج — إضافة وتعديل المنتجات والفئات والصور.</p>
            </div>
        </div>

        {{-- Steps --}}
        @foreach ($steps as $i => $step)
            <section class="{{ $card }}">
                {{-- English --}}
                <div x-show="lang === 'en'">
                    <div class="flex items-center gap-3">
                        <span class="{{ $num }}">{{ $i + 1 }}</span>
                        <h3 class="{{ $h }}">{{ $step['icon'] }} {!! $step['title_en'] !!}</h3>
                    </div>
                    <ul class="{{ $ul }}">
                        @foreach ($step['items_en'] as $item)
                            <li>{!! $item !!}</li>
                        @endforeach
                    </ul>
                </div>
                {{-- Arabic --}}
                <div x-show="lang === 'ar'" dir="rtl" class="text-end">
                    <div class="flex items-center gap-3">
                        <span class="{{ $num }}">{{ $i + 1 }}</span>
                        <h3 class="{{ $h }}">{{ $step['icon'] }} {!! $step['title_ar'] !!}</h3>
                    </div>
                    <ul class="{{ $ul }}">
                        @foreach ($step['items_ar'] as $item)
                            <li>{!! $item !!}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
