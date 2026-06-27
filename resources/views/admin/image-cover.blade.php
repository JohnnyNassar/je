<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cover logo — {{ $product->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #0f172a; color: #e2e8f0; }
        header { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; background: #1e293b; border-bottom: 1px solid #334155; position: sticky; top: 0; z-index: 10; }
        header h1 { font-size: .95rem; font-weight: 600; margin: 0; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        a.back { color: #94a3b8; text-decoration: none; font-size: .85rem; }
        a.back:hover { color: #e2e8f0; }
        .toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding: .75rem 1rem; background: #1e293b; border-bottom: 1px solid #334155; }
        .toolbar label { font-size: .8rem; color: #94a3b8; display: inline-flex; align-items: center; gap: .35rem; }
        input[type=color] { width: 38px; height: 30px; border: 1px solid #475569; border-radius: 6px; background: none; padding: 0; cursor: pointer; }
        button { font: inherit; font-size: .82rem; padding: .45rem .8rem; border-radius: 7px; border: 1px solid #475569; background: #334155; color: #e2e8f0; cursor: pointer; }
        button:hover { background: #3f4d63; }
        button.primary { background: #2563eb; border-color: #2563eb; }
        button.primary:hover { background: #1d4ed8; }
        button.ghost { background: transparent; }
        button:disabled { opacity: .4; cursor: not-allowed; }
        .swatch { width: 26px; height: 26px; border-radius: 6px; border: 1px solid #475569; cursor: pointer; padding: 0; }
        .layout { display: flex; gap: 0; align-items: flex-start; }
        .thumbs { width: 130px; flex: 0 0 130px; padding: 1rem .5rem; display: flex; flex-direction: column; gap: .6rem; max-height: calc(100vh - 110px); overflow-y: auto; border-right: 1px solid #334155; }
        .thumb { position: relative; border: 2px solid transparent; border-radius: 8px; overflow: hidden; cursor: pointer; background: #fff; padding: 0; }
        .thumb.active { border-color: #2563eb; }
        .thumb img { width: 100%; height: 84px; object-fit: cover; display: block; }
        .thumb .cap { font-size: .68rem; color: #cbd5e1; background: #1e293b; padding: .2rem .35rem; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .thumb .edited { position: absolute; top: 4px; right: 4px; background: #16a34a; color: #fff; font-size: .6rem; padding: 1px 5px; border-radius: 999px; }
        .stage { flex: 1; padding: 1rem; display: flex; justify-content: center; }
        .canvas-wrap { position: relative; max-width: 820px; width: 100%; }
        canvas { width: 100%; height: auto; display: block; border-radius: 8px; background: #fff; touch-action: none; cursor: crosshair; box-shadow: 0 10px 30px rgba(0,0,0,.4); }
        .hint { text-align: center; color: #94a3b8; font-size: .8rem; padding: 0 1rem 1.5rem; }
        .toast { position: fixed; bottom: 1rem; left: 50%; transform: translateX(-50%); background: #16a34a; color: #fff; padding: .6rem 1rem; border-radius: 8px; font-size: .85rem; opacity: 0; transition: opacity .2s; pointer-events: none; }
        .toast.show { opacity: 1; }
        .toast.error { background: #dc2626; }
    </style>
</head>
<body>
    <header>
        <a class="back" href="/admin/products">&larr; Products</a>
        <h1>Cover logo — {{ $product->name }}</h1>
    </header>

    <div class="toolbar">
        <label>Color
            <input type="color" id="color" value="#ffffff">
        </label>
        <button class="swatch" style="background:#ffffff" data-color="#ffffff" title="White"></button>
        <button class="swatch" style="background:#000000" data-color="#000000" title="Black"></button>
        <span style="flex:1"></span>
        <button id="undo" class="ghost" disabled>Undo</button>
        <button id="reset" class="ghost" disabled>Reset</button>
        <button id="save" class="primary">Save this image</button>
    </div>

    <div class="layout">
        <div class="thumbs" id="thumbs">
            @foreach ($images as $i => $im)
                <button class="thumb" data-index="{{ $i }}" data-path="{{ $im['path'] }}" data-url="{{ $im['url'] }}" data-mime="{{ $im['mime'] }}">
                    <img src="{{ $im['url'] }}" alt="">
                    <span class="cap">{{ $im['label'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="stage">
            <div class="canvas-wrap">
                <canvas id="canvas"></canvas>
            </div>
        </div>
    </div>
    <p class="hint">Pick an image on the left, drag across the logo to drop a colored box over it, then “Save this image”. Repeat for each image. The original of each is backed up the first time you save it.</p>

    <div class="toast" id="toast"></div>

    <script>
        const SAVE_URL = @json(route('admin.image-cover.save', $product));
        const CSRF     = document.querySelector('meta[name=csrf-token]').content;

        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const colorInput = document.getElementById('color');
        const undoBtn = document.getElementById('undo');
        const resetBtn = document.getElementById('reset');
        const saveBtn = document.getElementById('save');
        const toast = document.getElementById('toast');
        const thumbsEl = document.getElementById('thumbs');

        let img = new Image();
        let boxes = [];          // committed rects for the CURRENT image
        let drag = null;         // in-progress rect
        let current = null;      // { path, mime, thumbBtn }

        function loadImage(url, onready) {
            const next = new Image();
            next.onload = () => {
                img = next;
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                boxes = [];
                redraw();
                if (onready) onready();
            };
            next.src = url;
        }

        function selectThumb(btn) {
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            current = { path: btn.dataset.path, mime: btn.dataset.mime, thumbBtn: btn };
            loadImage(btn.dataset.url);
        }

        function redraw() {
            if (!img.naturalWidth) return;
            ctx.drawImage(img, 0, 0);
            for (const b of boxes) paintBox(b);
            if (drag) paintBox(drag);
            undoBtn.disabled = boxes.length === 0;
            resetBtn.disabled = boxes.length === 0;
        }

        function paintBox(b) {
            ctx.fillStyle = b.color;
            ctx.fillRect(b.x, b.y, b.w, b.h);
        }

        function toImageCoords(e) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: (e.clientX - rect.left) * (canvas.width / rect.width),
                y: (e.clientY - rect.top) * (canvas.height / rect.height),
            };
        }

        canvas.addEventListener('pointerdown', (e) => {
            if (!current) return;
            canvas.setPointerCapture(e.pointerId);
            const p = toImageCoords(e);
            drag = { x: p.x, y: p.y, w: 0, h: 0, color: colorInput.value };
        });
        canvas.addEventListener('pointermove', (e) => {
            if (!drag) return;
            const p = toImageCoords(e);
            drag.w = p.x - drag.x;
            drag.h = p.y - drag.y;
            redraw();
        });
        function endDrag() {
            if (!drag) return;
            let { x, y, w, h, color } = drag;
            if (w < 0) { x += w; w = -w; }
            if (h < 0) { y += h; h = -h; }
            if (w > 4 && h > 4) boxes.push({ x, y, w, h, color });
            drag = null;
            redraw();
        }
        canvas.addEventListener('pointerup', endDrag);
        canvas.addEventListener('pointercancel', endDrag);

        document.querySelectorAll('.swatch').forEach(s =>
            s.addEventListener('click', () => { colorInput.value = s.dataset.color; }));
        undoBtn.addEventListener('click', () => { boxes.pop(); redraw(); });
        resetBtn.addEventListener('click', () => { boxes = []; redraw(); });
        thumbsEl.querySelectorAll('.thumb').forEach(btn =>
            btn.addEventListener('click', () => selectThumb(btn)));

        function showToast(msg, isError) {
            toast.textContent = msg;
            toast.className = 'toast show' + (isError ? ' error' : '');
            setTimeout(() => { toast.className = 'toast'; }, 2500);
        }

        saveBtn.addEventListener('click', async () => {
            if (!current) { showToast('Pick an image first', true); return; }
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
            try {
                const dataUrl = canvas.toDataURL(current.mime, 0.92);
                const res = await fetch(SAVE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ image: dataUrl, path: current.path }),
                });
                const json = await res.json();
                if (json.ok) {
                    showToast('Saved ✓');
                    // Mark the thumb as edited and refresh it + the canvas to the saved file.
                    const btn = current.thumbBtn;
                    btn.dataset.url = json.image_url;
                    btn.querySelector('img').src = json.image_url;
                    if (!btn.querySelector('.edited')) {
                        const tag = document.createElement('span');
                        tag.className = 'edited';
                        tag.textContent = '✓';
                        btn.appendChild(tag);
                    }
                    loadImage(json.image_url);
                } else {
                    showToast(json.message || 'Save failed', true);
                }
            } catch (err) {
                showToast('Save failed', true);
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save this image';
            }
        });

        // Start on the first image.
        const first = thumbsEl.querySelector('.thumb');
        if (first) selectThumb(first);
    </script>
</body>
</html>
