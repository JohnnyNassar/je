<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * "Cover logo" tool — paints solid boxes over a product's images to hide a
 * supplier logo/watermark (typically top-centre) without cropping the photo.
 *
 * Covers every image the product shows: the Main image, each Gallery image,
 * and each variant's image override. The browser does the compositing on a
 * <canvas>; the server decodes the result and overwrites the file in place
 * (keeping a one-time backup of the original first). Because each file is
 * overwritten at its existing path, no DB columns change.
 */
class ImageCoverController extends Controller
{
    public function show(Product $product)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $images = $this->images($product);
        abort_if($images === [], 404, 'This product has no images to edit.');

        return view('admin.image-cover', [
            'product' => $product,
            'images'  => $images,
        ]);
    }

    public function save(Request $request, Product $product)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $request->validate([
            'image' => ['required', 'string'],
            'path'  => ['required', 'string'],
        ]);

        // Only allow overwriting paths that actually belong to this product.
        $allowed = array_column($this->images($product), 'path');
        abort_unless(in_array($data['path'], $allowed, true), 403, 'That image does not belong to this product.');

        if (! preg_match('/^data:image\/(jpeg|png);base64,/', $data['image'])) {
            return response()->json(['ok' => false, 'message' => 'Unexpected image format.'], 422);
        }

        $binary = base64_decode(substr($data['image'], strpos($data['image'], ',') + 1), true);
        if ($binary === false) {
            return response()->json(['ok' => false, 'message' => 'Could not decode the image.'], 422);
        }

        $disk = Storage::disk('public');
        $path = $data['path'];

        // One-time backup of the original (with the logo) so edits are reversible.
        // Bail out before overwriting if the backup can't be made — otherwise a
        // failed write below would leave us with neither the edit nor a backup.
        $backup = '_originals/' . basename($path);
        if ($disk->exists($path) && ! $disk->exists($backup)) {
            if (! $disk->copy($path, $backup)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Could not back up the original — check storage permissions on the server.',
                ], 500);
            }
        }

        // The public disk is configured with throw=false, so put() returns false
        // (no exception) when the web-server user can't write. Check it explicitly
        // so we never report success while nothing was actually saved.
        if (! $disk->put($path, $binary)) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not save the image — the file is not writable on the server.',
            ], 500);
        }

        $product->touch();

        return response()->json([
            'ok'        => true,
            'path'      => $path,
            'image_url' => asset('storage/' . $path) . '?t=' . now()->timestamp,
        ]);
    }

    /**
     * Every editable image for the product: Main, Gallery, and variant images.
     * Each entry: ['path' => storage path, 'url' => cache-busted asset URL,
     * 'label' => human label, 'mime' => image/jpeg|png].
     *
     * @return array<int, array{path:string,url:string,label:string,mime:string}>
     */
    private function images(Product $product): array
    {
        $out = [];
        $seen = [];
        $t = now()->timestamp;

        $add = function (?string $path, string $label) use (&$out, &$seen, $t) {
            if (! $path || isset($seen[$path])) {
                return;
            }
            $seen[$path] = true;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $out[] = [
                'path'  => $path,
                'url'   => asset('storage/' . $path) . '?t=' . $t,
                'label' => $label,
                'mime'  => $ext === 'png' ? 'image/png' : 'image/jpeg',
            ];
        };

        $add($product->image_path, 'Main image');

        foreach ((array) $product->gallery as $i => $path) {
            $add($path, 'Gallery ' . ($i + 1));
        }

        foreach ($product->variants as $variant) {
            $add($variant->image_path, 'Variant: ' . ($variant->name ?: ('#' . $variant->id)));
        }

        return $out;
    }
}
