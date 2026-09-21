<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Pinned products lead the plain shop page (no category, no search).
            // They are not a reserved block — they simply sort first inside the
            // normal paginated grid.
            if (! Schema::hasColumn('products', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_featured');
            }
            // Orders the pinned handful among themselves, most recent first, so
            // re-pinning is how you move one back to the front. Stamped by the
            // model, never edited by hand.
            if (! Schema::hasColumn('products', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            }
        });

        // DDL isn't transactional here, so a half-applied run must be re-runnable.
        if (! $this->hasIndex('products_pinned_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['is_pinned', 'pinned_at'], 'products_pinned_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('products_pinned_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_pinned_index');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'pinned_at']);
        });
    }

    private function hasIndex(string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'products')
            ->where('index_name', $name)
            ->exists();
    }
};
