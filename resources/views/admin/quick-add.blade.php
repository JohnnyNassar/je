@php
    $defaultCurrency = \App\Models\Setting::get('currency_symbol', '$');
    $canViewCost = auth()->user()?->canViewCost();
    $currencyPos = \App\Models\Setting::get('currency_position', 'before');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quick Add — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0f4248">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Joreption">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: dark; }
        html, body { background: #0a272c; }
        body { padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); }
        .qa-card { transition: transform 250ms cubic-bezier(.2,.8,.2,1), opacity 200ms; touch-action: pan-y; }
        .qa-card.swiping { transition: none; }
        @keyframes pulseDot { 0%,100% { transform: scale(1); opacity: 1 } 50% { transform: scale(1.3); opacity: 0.6 } }
        .recording-dot { animation: pulseDot 1s infinite; }
    </style>
</head>
<body class="font-sans antialiased bg-brand-950 text-white min-h-screen">
<div x-data="quickAdd()" x-cloak class="min-h-screen flex flex-col">
    {{-- Header --}}
    <header class="sticky top-0 z-30 bg-brand-900/95 backdrop-blur border-b border-white/10">
        <div class="px-4 py-3 flex items-center justify-between gap-3">
            <a href="/admin" class="flex items-center gap-2 text-white shrink-0">
                <img src="{{ asset('images/logo.jpg') }}" alt="" class="w-8 h-8 rounded-md object-cover ring-1 ring-white/20">
                <span class="text-sm font-semibold">{{ __('Quick Add') ?? 'Quick Add' }}</span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" @click="cycleLang()"
                        class="text-xs font-medium text-white/70 hover:text-white px-2 py-1 rounded-md border border-white/10">
                    <span x-text="langLabel"></span>
                </button>
                <span x-show="queue.length > 0" x-cloak
                      class="inline-flex items-center justify-center min-w-7 h-7 px-2 text-xs font-bold rounded-full bg-accent-600 text-white">
                    <span x-text="queue.length"></span>
                </span>
            </div>
        </div>
    </header>

    {{-- Flash --}}
    @if (session('status'))
        <div class="mx-4 mt-4 rounded-lg bg-green-500/20 border border-green-400/30 text-green-100 px-4 py-2 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- View: empty (no photos yet) --}}
    <main x-show="view === 'empty'" x-cloak class="flex-1 flex flex-col items-center justify-center px-6 py-10 text-center">
        <div class="w-28 h-28 rounded-3xl bg-brand-800/50 flex items-center justify-center mb-6 ring-1 ring-white/10">
            <svg class="w-12 h-12 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-2">Add a product</h1>
        <p class="text-white/60 text-sm mb-8 max-w-xs">Snap a photo. Set price and stock. Publish. Repeat.</p>

        <label class="w-full max-w-xs">
            <input type="file" accept="image/*" capture="environment" class="hidden"
                   @change="onCapture($event)" multiple>
            <span class="block w-full text-center bg-accent-600 hover:bg-accent-700 active:bg-accent-800 text-white font-bold text-lg px-6 py-5 rounded-2xl shadow-lg cursor-pointer">
                📷 Take Photo
            </span>
        </label>
        <p class="mt-3 text-xs text-white/40">You can select multiple at once</p>
    </main>

    {{-- View: edit one item --}}
    <main x-show="view === 'edit'" x-cloak class="flex-1 px-4 py-4">
        <template x-if="current">
            <form @submit.prevent="publishCurrent()" class="space-y-4">
                {{-- Image preview --}}
                <div class="relative">
                    <img :src="current.preview" class="w-full aspect-square object-cover rounded-2xl ring-1 ring-white/10">
                    <button type="button" @click="retakeCurrent()"
                            class="absolute top-3 end-3 inline-flex items-center gap-1.5 bg-black/60 backdrop-blur text-white text-xs px-3 py-1.5 rounded-full">
                        📷 Retake
                    </button>
                    <template x-if="queue.length > 1">
                        <div class="absolute bottom-3 start-3 inline-flex items-center gap-1.5 bg-black/60 backdrop-blur text-white text-xs px-3 py-1.5 rounded-full">
                            <span x-text="(currentIndex + 1) + ' / ' + queue.length"></span>
                        </div>
                    </template>
                </div>

                {{-- Category --}}
                @if (! $categories->isEmpty())
                    <div>
                        <select x-model="current.category_id"
                                class="w-full rounded-xl bg-white/5 border-white/10 text-white focus:bg-white/10 focus:border-brand-400 focus:ring-0 text-base">
                            <option value="" class="bg-brand-900">— No category —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" class="bg-brand-900">{{ $cat->name_en }}@if ($cat->name_ar) &middot; {{ $cat->name_ar }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Name fields --}}
                <div class="space-y-2">
                    <input type="text" x-model="current.name_ar" placeholder="اسم المنتج" dir="rtl"
                           class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-white/40 focus:bg-white/10 focus:border-brand-400 focus:ring-0 text-base">
                    <input type="text" x-model="current.name_en" placeholder="Product name (English)" dir="ltr"
                           class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-white/40 focus:bg-white/10 focus:border-brand-400 focus:ring-0 text-base">
                </div>

                {{-- Description fields with voice button --}}
                <div class="space-y-2">
                    <div class="relative">
                        <textarea x-model="current.description_ar" placeholder="وصف المنتج" dir="rtl" rows="3"
                                  class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-white/40 focus:bg-white/10 focus:border-brand-400 focus:ring-0 text-sm pe-12"></textarea>
                        <button type="button" @click="toggleVoice('ar')" x-show="hasVoice"
                                :class="recording === 'ar' ? 'bg-red-600 text-white' : 'bg-white/10 text-white/80'"
                                class="absolute top-2 end-2 w-9 h-9 rounded-full flex items-center justify-center transition" aria-label="Voice input (Arabic)">
                            <svg x-show="recording !== 'ar'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 11-14 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v3M8 21h8M12 3a3 3 0 00-3 3v6a3 3 0 006 0V6a3 3 0 00-3-3z"/></svg>
                            <span x-show="recording === 'ar'" class="w-3 h-3 bg-white rounded-full recording-dot"></span>
                        </button>
                    </div>
                    <textarea x-model="current.description_en" placeholder="Description (English)" dir="ltr" rows="3"
                              class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-white/40 focus:bg-white/10 focus:border-brand-400 focus:ring-0 text-sm"></textarea>
                </div>

                {{-- Price + stock --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-white/60 mb-1.5">Price</label>
                        <div class="relative">
                            <input type="number" x-model="current.price" min="0" step="0.01" required dir="ltr"
                                   class="w-full rounded-xl bg-white/5 border-white/10 text-white text-lg font-semibold focus:bg-white/10 focus:border-brand-400 focus:ring-0 ps-12">
                            <span class="absolute top-1/2 -translate-y-1/2 start-3 text-white/60 text-sm">{{ $defaultCurrency }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-white/60 mb-1.5">Stock</label>
                        <input type="number" x-model="current.stock" min="0" required dir="ltr"
                               class="w-full rounded-xl bg-white/5 border-white/10 text-white text-lg font-semibold focus:bg-white/10 focus:border-brand-400 focus:ring-0">
                    </div>
                </div>

                @if($canViewCost)
                {{-- Cost price (gated — never shown to customers) --}}
                <div>
                    <label class="block text-xs text-white/60 mb-1.5">Cost price <span class="text-white/40">(what you paid — admin only)</span></label>
                    <div class="relative">
                        <input type="number" x-model="current.cost_price" min="0" step="0.01" dir="ltr"
                               class="w-full rounded-xl bg-white/5 border-white/10 text-white focus:bg-white/10 focus:border-brand-400 focus:ring-0 ps-12">
                        <span class="absolute top-1/2 -translate-y-1/2 start-3 text-white/60 text-sm">{{ $defaultCurrency }}</span>
                    </div>
                    <template x-if="current.cost_price && Number(current.cost_price) > 0 && Number(current.price) > 0">
                        <div class="mt-1.5 text-xs font-semibold"
                             :class="Number(current.price) - Number(current.cost_price) < 0 ? 'text-accent-400' : 'text-brand-300'">
                            <span x-text="'Profit: ' + ((Number(current.price) - Number(current.cost_price)).toFixed(2)) + ' (' + Math.round(((Number(current.price) - Number(current.cost_price)) / Number(current.price)) * 100) + '%)'"></span>
                        </div>
                    </template>
                </div>
                @endif

                {{-- Compare-at price (optional, for Save% badge) --}}
                <div>
                    <label class="block text-xs text-white/60 mb-1.5">Original price <span class="text-white/40">(optional — shows "Save X%" badge)</span></label>
                    <div class="relative">
                        <input type="number" x-model="current.compare_at_price" min="0" step="0.01" dir="ltr"
                               class="w-full rounded-xl bg-white/5 border-white/10 text-white focus:bg-white/10 focus:border-brand-400 focus:ring-0 ps-12">
                        <span class="absolute top-1/2 -translate-y-1/2 start-3 text-white/60 text-sm">{{ $defaultCurrency }}</span>
                    </div>
                    <template x-if="current.compare_at_price && Number(current.compare_at_price) > Number(current.price)">
                        <div class="mt-1.5 text-xs text-accent-400 font-semibold">
                            <span x-text="'Will show: Save ' + Math.round(((current.compare_at_price - current.price) / current.compare_at_price) * 100) + '%'"></span>
                        </div>
                    </template>
                </div>

                {{-- Active + Featured toggles --}}
                <label class="flex items-center justify-between px-1 py-2">
                    <div>
                        <div class="text-sm font-medium">Publish immediately</div>
                        <div class="text-xs text-white/50">Off = save as draft for review later</div>
                    </div>
                    <input type="checkbox" x-model="current.is_active"
                           class="rounded text-brand-500 bg-white/10 border-white/20 focus:ring-brand-500 focus:ring-offset-brand-950 w-6 h-6">
                </label>

                <label class="flex items-center justify-between px-1 py-2">
                    <div>
                        <div class="text-sm font-medium">⭐ Featured on home page</div>
                        <div class="text-xs text-white/50">Highlights this product at the top of the catalog</div>
                    </div>
                    <input type="checkbox" x-model="current.is_featured"
                           class="rounded text-accent-500 bg-white/10 border-white/20 focus:ring-accent-500 focus:ring-offset-brand-950 w-6 h-6">
                </label>

                {{-- Save status --}}
                <div x-show="saveStatus" x-cloak class="text-center text-sm" :class="saveStatusClass">
                    <span x-text="saveStatus"></span>
                </div>

                {{-- Buttons --}}
                <div class="grid grid-cols-3 gap-2 pt-2">
                    <button type="button" @click="discardCurrent()"
                            class="px-3 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-white text-sm font-medium">
                        Discard
                    </button>
                    <button type="button" @click="goNext()" :disabled="!canGoNext()"
                            :class="canGoNext() ? 'bg-white/10 hover:bg-white/15 text-white' : 'bg-white/5 text-white/30'"
                            class="px-3 py-3.5 rounded-xl text-sm font-medium">
                        Skip →
                    </button>
                    <button type="submit" :disabled="saving"
                            class="px-3 py-3.5 rounded-xl bg-accent-600 hover:bg-accent-700 disabled:opacity-50 text-white text-sm font-bold">
                        <span x-show="!saving">✓ Publish</span>
                        <span x-show="saving">Saving…</span>
                    </button>
                </div>

                {{-- Add more photos --}}
                <label class="block">
                    <input type="file" accept="image/*" capture="environment" class="hidden"
                           @change="onCapture($event)" multiple>
                    <span class="block w-full text-center bg-brand-800/50 hover:bg-brand-800 text-white/80 font-medium py-3 rounded-xl border border-white/10 cursor-pointer">
                        + Add more photos
                    </span>
                </label>
            </form>
        </template>
    </main>

    {{-- View: just published — show URL with copy --}}
    <main x-show="view === 'published'" x-cloak class="flex-1 flex flex-col items-center justify-center px-5 py-8">
        <div class="w-full max-w-md">
            <div class="bg-brand-800/50 ring-1 ring-white/10 rounded-2xl p-6 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-green-500/20 ring-2 ring-green-400/30 mb-4">
                    <svg class="w-7 h-7 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold mb-1">Published ✓</h2>
                <p class="text-white/70 text-sm mb-5 line-clamp-2" x-text="lastPublished.name"></p>

                <div class="bg-black/30 rounded-lg p-3 text-start mb-3 break-all text-xs font-mono text-white/80 ring-1 ring-white/10"
                     x-text="lastPublished.public_url" dir="ltr"></div>

                <button type="button" @click="copyLink()"
                        class="w-full inline-flex items-center justify-center gap-2 bg-accent-600 hover:bg-accent-700 active:bg-accent-800 text-white font-bold py-3.5 rounded-xl shadow-lg mb-2">
                    <svg x-show="!copied" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg x-show="copied" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span x-show="!copied">📋 Copy link</span>
                    <span x-show="copied" x-cloak>Copied! Paste into WhatsApp</span>
                </button>

                <p class="text-xs text-white/40 mb-5">Paste this into your WhatsApp group to drive traffic</p>

                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <input type="file" accept="image/*" capture="environment" class="hidden"
                               @change="onCapture($event); view = queue.length ? 'edit' : 'empty'">
                        <span class="block w-full text-center bg-white/10 hover:bg-white/15 text-white font-medium py-3 rounded-xl cursor-pointer text-sm">
                            📷 New photo
                        </span>
                    </label>
                    <button type="button" @click="goToQueueOrEmpty()"
                            class="px-3 py-3 rounded-xl bg-brand-800/70 hover:bg-brand-800 text-white text-sm font-medium">
                        <span x-text="queue.length > 0 ? `Next (${queue.length}) →` : 'Done'"></span>
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function quickAdd() {
    return {
        // State
        view: 'empty',           // 'empty' | 'edit' | 'published'
        queue: [],
        currentIndex: 0,
        saving: false,
        saveStatus: '',
        saveStatusClass: 'text-white/60',
        recording: null,
        recognizer: null,
        hasVoice: false,
        langLabel: 'EN',
        lastPublished: { name: '', public_url: '' },
        lastCategoryId: '',
        copied: false,

        get current() { return this.queue[this.currentIndex] || null; },

        init() {
            this.hasVoice = !!(window.SpeechRecognition || window.webkitSpeechRecognition);
            this.langLabel = '{{ app()->getLocale() === "ar" ? "AR" : "EN" }}';

            // Register service worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch((e) => console.warn('SW register failed', e));
            }
        },

        cycleLang() {
            // Toggle html dir/lang for visual feedback only
            const html = document.documentElement;
            const next = html.lang === 'ar' ? 'en' : 'ar';
            html.lang = next;
            html.dir = next === 'ar' ? 'rtl' : 'ltr';
            this.langLabel = next === 'ar' ? 'AR' : 'EN';
        },

        async onCapture(event) {
            const files = Array.from(event.target.files || []);
            event.target.value = '';
            if (!files.length) return;

            for (const file of files) {
                try {
                    const compressed = await this.compressImage(file);
                    const preview = URL.createObjectURL(compressed);
                    this.queue.push({
                        id: crypto.randomUUID(),
                        file: compressed,
                        preview,
                        category_id: this.lastCategoryId,
                        name_ar: '',
                        name_en: '',
                        description_ar: '',
                        description_en: '',
                        price: '',
                        cost_price: '',
                        compare_at_price: '',
                        stock: 1,
                        is_active: true,
                        is_featured: false,
                        published: false,
                    });
                } catch (e) {
                    console.error('Compression failed', e);
                }
            }
            if (this.view === 'empty' && this.queue.length > 0) {
                this.view = 'edit';
                this.currentIndex = 0;
            }
        },

        async compressImage(file, maxDim = 1280, quality = 0.85) {
            try {
                const bitmap = await createImageBitmap(file);
                const ratio = Math.min(maxDim / bitmap.width, maxDim / bitmap.height, 1);
                const w = Math.round(bitmap.width * ratio);
                const h = Math.round(bitmap.height * ratio);
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
                return await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
            } catch (e) {
                console.warn('Compression unavailable, using original', e);
                return file;
            }
        },

        canGoNext() {
            return this.currentIndex < this.queue.length - 1;
        },

        goNext() {
            if (this.canGoNext()) this.currentIndex++;
        },

        discardCurrent() {
            if (!this.current) return;
            URL.revokeObjectURL(this.current.preview);
            this.queue.splice(this.currentIndex, 1);
            if (this.queue.length === 0) {
                this.view = 'empty';
                this.currentIndex = 0;
            } else if (this.currentIndex >= this.queue.length) {
                this.currentIndex = this.queue.length - 1;
            }
        },

        retakeCurrent() {
            const inp = document.createElement('input');
            inp.type = 'file';
            inp.accept = 'image/*';
            inp.capture = 'environment';
            inp.onchange = async (e) => {
                const f = e.target.files?.[0];
                if (!f) return;
                const compressed = await this.compressImage(f);
                URL.revokeObjectURL(this.current.preview);
                this.current.file = compressed;
                this.current.preview = URL.createObjectURL(compressed);
            };
            inp.click();
        },

        toggleVoice(lang) {
            const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!Recognition) return;

            if (this.recording === lang) {
                this.recognizer?.stop();
                this.recording = null;
                return;
            }
            if (this.recognizer) this.recognizer.stop();

            const r = new Recognition();
            r.lang = lang === 'ar' ? 'ar-JO' : 'en-US';
            r.interimResults = true;
            r.continuous = true;
            const targetKey = lang === 'ar' ? 'description_ar' : 'description_en';
            const initial = this.current[targetKey] || '';
            let finalText = initial;

            r.onresult = (event) => {
                let interim = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const res = event.results[i];
                    if (res.isFinal) {
                        finalText += (finalText && !finalText.endsWith(' ') ? ' ' : '') + res[0].transcript;
                    } else {
                        interim += res[0].transcript;
                    }
                }
                this.current[targetKey] = (finalText + (interim ? ' ' + interim : '')).trim();
            };
            r.onend = () => { this.recording = null; this.recognizer = null; };
            r.onerror = (e) => { console.warn('STT error', e); this.recording = null; this.recognizer = null; };

            try {
                r.start();
                this.recognizer = r;
                this.recording = lang;
            } catch (e) {
                console.warn(e);
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.lastPublished.public_url);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (e) {
                // Fallback: select text
                const el = document.createElement('textarea');
                el.value = this.lastPublished.public_url;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            }
        },

        goToQueueOrEmpty() {
            if (this.queue.length > 0) {
                this.view = 'edit';
            } else {
                this.view = 'empty';
            }
        },

        async publishCurrent() {
            if (!this.current || this.saving) return;
            if (!this.current.name_ar && !this.current.name_en) {
                this.saveStatus = '⚠ Add a name in Arabic or English';
                this.saveStatusClass = 'text-amber-300';
                return;
            }
            this.saving = true;
            this.saveStatus = '';

            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            fd.append('image', this.current.file, 'product.jpg');
            fd.append('category_id', this.current.category_id || '');
            fd.append('name_ar', this.current.name_ar || '');
            fd.append('name_en', this.current.name_en || '');
            fd.append('description_ar', this.current.description_ar || '');
            fd.append('description_en', this.current.description_en || '');
            fd.append('price', this.current.price || 0);
            if (this.current.cost_price && Number(this.current.cost_price) > 0) {
                fd.append('cost_price', this.current.cost_price);
            }
            if (this.current.compare_at_price && Number(this.current.compare_at_price) > 0) {
                fd.append('compare_at_price', this.current.compare_at_price);
            }
            fd.append('stock', this.current.stock || 1);
            fd.append('is_active', this.current.is_active ? '1' : '0');
            fd.append('is_featured', this.current.is_featured ? '1' : '0');

            // Remember the last-used category so the next photo in the queue inherits it
            this.lastCategoryId = this.current.category_id || '';

            try {
                const res = await fetch('{{ route('admin.quick-add.store') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || ('Save failed (' + res.status + ')'));
                }
                const json = await res.json();
                URL.revokeObjectURL(this.current.preview);
                this.queue.splice(this.currentIndex, 1);
                if (this.currentIndex >= this.queue.length && this.queue.length > 0) {
                    this.currentIndex = this.queue.length - 1;
                }

                this.lastPublished = {
                    name: json.product?.name || 'Product',
                    public_url: json.product?.public_url || '',
                };
                this.copied = false;
                this.view = 'published';
                this.saveStatus = '';
            } catch (e) {
                this.saveStatus = '✗ ' + e.message;
                this.saveStatusClass = 'text-red-300';
            } finally {
                this.saving = false;
            }
        },
    }
}
</script>
</body>
</html>
