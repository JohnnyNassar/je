<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * "Cover logo" tool — paints solid boxes over a product's main image to hide a
 * supplier logo/watermark (typically top-centre) without cropping the photo.
 *
 * Prototype scope: operates on the product's Main image (image_path) only.
 * The browser does the compositing on a <canvas>; the server just decodes the
 * resulting image and overwrites the file (keeping a one-time backup first).
 */
class ImageCoverController extends Controller
{
    public function show(Product $product)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless((bool) $product->image_path, 404, 'This product has no main image to edit.');

        $ext = strtolower(pathinfo($product->image_path, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

        return view('admin.image-cover', [
            'product'  => $product,
            'imageUrl' => asset('storage/' . $product->image_path) . '?t=' . now()->timestamp,
            'mime'     => $mime,
        ]);
    }

    public function save(Request $request, Product $product)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless((bool) $product->image_path, 404);

        $data = $request->validate([
            'image' => ['required', 'string'],
        ]);

        // Expect a data URL: data:image/jpeg;base64,XXXX
        if (! preg_match('/^data:image\/(jpeg|png);base64,/', $data['image'], $m)) {
            return response()->json(['ok' => false, 'message' => 'Unexpected image format.'], 422);
        }

        $binary = base64_decode(substr($data['image'], strpos($data['image'], ',') + 1), true);
        if ($binary === false) {
            return response()->json(['ok' => false, 'message' => 'Could not decode the image.'], 422);
        }

        $disk = Storage::disk('public');
        $path = $product->image_path;

        // One-time backup of the original (with the logo) so edits are reversible
        // while testing. Kept out of the media library under _originals/.
        $backup = '_originals/' . basename($path);
        if ($disk->exists($path) && ! $disk->exists($backup)) {
            $disk->copy($path, $backup);
        }

        $disk->put($path, $binary);

        // Touch the product so any "updated_at" / cache reflects the change.
        $product->touch();

        return response()->json([
            'ok'        => true,
            'image_url' => asset('storage/' . $path) . '?t=' . now()->timestamp,
        ]);
    }
}
