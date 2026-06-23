<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The gallery is now the single source of truth for product images, with
     * its first image as the main photo. Existing products only have a cover
     * (image_path) and an empty gallery — fold that cover in as image #1 so
     * they show up in the unified list and render consistently.
     */
    public function up(): void
    {
        foreach (DB::table('products')->select('id', 'image_path', 'gallery')->get() as $p) {
            $gallery = $p->gallery ? json_decode($p->gallery, true) : [];
            $gallery = is_array($gallery) ? array_values(array_filter($gallery)) : [];

            if (empty($gallery) && ! empty($p->image_path)) {
                DB::table('products')
                    ->where('id', $p->id)
                    ->update(['gallery' => json_encode([$p->image_path])]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: leave galleries in place on rollback.
    }
};
