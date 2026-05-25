@php($isAr = app()->getLocale() === 'ar')
@php($wa = \App\Models\Setting::get('admin_whatsapp'))
<x-layouts.shop>
    <article class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-6 sm:p-8 leading-relaxed text-gray-700">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ ($isAr ? 'آخر تحديث: ' : 'Last updated: ') . date('F Y') }}</p>

        @if ($isAr)
            <p class="mb-4">تشرح هذه السياسة كيف نجمع معلوماتك ونستخدمها ونحميها عند استخدامك متجر {{ config('app.name') }} (الدفع عند الاستلام).</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">المعلومات التي نجمعها</h2>
            <ul class="list-disc ps-6 space-y-1">
                <li><strong>معلومات الطلب:</strong> الاسم، رقم الهاتف، المدينة والعنوان، وتفاصيل الطلب — لتجهيز طلبك وتوصيله.</li>
                <li><strong>معلومات الحساب (اختياري):</strong> الاسم والبريد الإلكتروني وكلمة مرور مشفّرة، إذا أنشأت حساباً.</li>
                <li><strong>بيانات الاستخدام:</strong> إحصاءات مجهّلة عبر Google Analytics (الصفحات التي تزورها، نوع الجهاز، الموقع التقريبي) باستخدام ملفات تعريف الارتباط. لا نرسل اسمك أو رقم هاتفك إلى Google.</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">كيف نستخدم معلوماتك</h2>
            <ul class="list-disc ps-6 space-y-1">
                <li>تجهيز طلباتك وتوصيلها والتواصل معك بشأنها.</li>
                <li>إدارة حسابك ونقاط الولاء المرتبطة بطلباتك.</li>
                <li>تحسين المتجر وفهم كيفية استخدامه.</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">ملفات تعريف الارتباط (Cookies)</h2>
            <p>نستخدم ملفات ضرورية (لعمل السلّة والجلسة) وملفات تحليلية (Google Analytics). يمكنك رفض أو حذف ملفات تعريف الارتباط من إعدادات متصفحك.</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">مشاركة المعلومات</h2>
            <p>نشارك معلومات التوصيل اللازمة مع مندوب التوصيل لإيصال طلبك، ونستخدم Google Analytics بشكل مجهّل. نحن <strong>لا نبيع</strong> بياناتك. وقد نُفصح عنها إذا تطلّب القانون ذلك.</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">الاحتفاظ بالبيانات وحقوقك</h2>
            <p>نحتفظ بمعلوماتك طالما لزم ذلك لإدارة الطلبات والحسابات والالتزامات القانونية. يحق لك طلب الاطّلاع على بياناتك أو تصحيحها أو حذفها بالتواصل معنا.</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">تواصل معنا</h2>
            <p>
                لأي استفسار حول خصوصيتك،
                @if ($wa)
                    راسلنا على واتساب:
                    <a href="https://wa.me/{{ $wa }}" target="_blank" class="text-brand-600 underline">{{ $wa }}</a>.
                @else
                    يرجى التواصل معنا عبر قنوات المتجر.
                @endif
            </p>
        @else
            <p class="mb-4">This policy explains how we collect, use and protect your information when you use {{ config('app.name') }} (Cash on Delivery).</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">Information we collect</h2>
            <ul class="list-disc ps-6 space-y-1">
                <li><strong>Order details:</strong> your name, phone number, city and address, and what you ordered — used to prepare and deliver your order.</li>
                <li><strong>Account details (optional):</strong> name, email and an encrypted password, if you create an account.</li>
                <li><strong>Usage data:</strong> anonymous statistics via Google Analytics (pages visited, device type, approximate location) using cookies. We do not send your name or phone number to Google.</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">How we use it</h2>
            <ul class="list-disc ps-6 space-y-1">
                <li>To process and deliver your orders and contact you about them.</li>
                <li>To manage your account and the loyalty points tied to your orders.</li>
                <li>To improve the store and understand how it's used.</li>
            </ul>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">Cookies</h2>
            <p>We use essential cookies (to run the cart and your session) and analytics cookies (Google Analytics). You can refuse or delete cookies in your browser settings.</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">Sharing</h2>
            <p>We share the delivery details needed with the courier to deliver your order, and use Google Analytics in an anonymized form. We do <strong>not</strong> sell your data. We may disclose information if required by law.</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">Data retention &amp; your rights</h2>
            <p>We keep your information for as long as needed to manage orders, accounts and legal obligations. You may request access to, correction of, or deletion of your data by contacting us.</p>

            <h2 class="text-lg font-semibold text-gray-900 mt-6 mb-2">Contact us</h2>
            <p>
                For any privacy question,
                @if ($wa)
                    message us on WhatsApp:
                    <a href="https://wa.me/{{ $wa }}" target="_blank" class="text-brand-600 underline">{{ $wa }}</a>.
                @else
                    please reach us through the store's channels.
                @endif
            </p>
        @endif
    </article>
</x-layouts.shop>
