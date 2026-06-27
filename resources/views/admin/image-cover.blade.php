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
        .stage { padding: 1rem; display: flex; justify-content: center; }
        .canvas-wrap { position: relative; max-width: 900px; width: 100%; }
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
        <button id="save" class="primary">Save</button>
    </div>

    <div class="stage">
        <div class="canvas-wrap">
            <canvas id="canvas"></canvas>
        </div>
    </div>
    <p class="hint">Drag across the logo to drop a colored box over it. Add as many boxes as you need. “Save” overwrites the image (the original is backed up the first time).</p>

    <div class="toast" id="toast"></div>

    <script>
        const IMAGE_URL = @json($imageUrl);
        const SAVE_URL  = @json(route('admin.image-cover.save', $product));
        const MIME      = @json($mime);
        const CSRF      = document.querySelector('meta[name=csrf-token]').content;

        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const colorInput = document.getElementById('color');
        const undoBtn = document.getElementById('undo');
        const resetBtn = document.getElementById('reset');
        const saveBtn = document.getElementById('save');
        const toast = document.getElementById('toast');

        let img = new Image();
        let boxes = [];          // committed rects {x,y,w,h,color} in image coordinates
        let drag = null;         // in-progress rect

        img.onload = () => {
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            redraw();
        };
        img.src = IMAGE_URL;

        function redraw() {
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

        // Map a pointer event to image-space coordinates.
        function toImageCoords(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return {
                x: (e.clientX - rect.left) * scaleX,
                y: (e.clientY - rect.top) * scaleY,
            };
        }

        canvas.addEventListener('pointerdown', (e) => {
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
            // Normalize negative width/height, ignore tiny accidental clicks.
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

        function showToast(msg, isError) {
            toast.textContent = msg;
            toast.className = 'toast show' + (isError ? ' error' : '');
            setTimeout(() => { toast.className = 'toast'; }, 2500);
        }

        saveBtn.addEventListener('click', async () => {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
            try {
                const dataUrl = canvas.toDataURL(MIME, 0.92);
                const res = await fetch(SAVE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ image: dataUrl }),
                });
                const json = await res.json();
                if (json.ok) {
                    showToast('Saved ✓');
                    boxes = [];
                    img = new Image();
                    img.onload = () => { canvas.width = img.naturalWidth; canvas.height = img.naturalHeight; redraw(); };
                    img.src = json.image_url; // cache-busted, reflects the saved file
                } else {
                    showToast(json.message || 'Save failed', true);
                }
            } catch (err) {
                showToast('Save failed', true);
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            }
        });
    </script>
</body>
</html>
