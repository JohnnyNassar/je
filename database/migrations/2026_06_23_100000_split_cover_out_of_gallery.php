<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restores the original two-field model: a separate cover (image_path) plus
     * a gallery of EXTRA images. The earlier merge had made the gallery hold all
     * images with the cover duplicated as its first entry. Here we drop that
     * leading duplicate so the gallery again holds only the additional photos.
     */
    public function up(): void
    {
        foreach (DB::table('products')->select('id', 'image_path', 'gallery')->get() as $p) {
            $gallery = $p->gallery ? json_decode($p->gallery, true) : [];
            $gallery = is_array($gallery) ? array_values(array_filter($gallery)) : [];

            // Drop the first image when it's the cover (the duplicated entry).
            if (! empty($gallery) && ! empty($p->image_path) && $gallery[0] === $p->image_path) {
                $rest = array_slice($gallery, 1);
                DB::table('products')->where('id', $p->id)->update([
                    'gallery' => empty($rest) ? null : json_encode(array_values($rest)),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: leave galleries as-is on rollback.
    }
};
