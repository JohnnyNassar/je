<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the coupon applied to an order. `total` stays the final, post-discount
 * amount the customer pays; `discount_total` is how much the coupon took off, and
 * `coupon_code` is a snapshot of the code used (kept even if the coupon is later
 * deleted/edited). Guarded with hasColumn so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'discount_total')) {
                $table->decimal('discount_total', 10, 2)->default(0)->after('total');
            }
            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('discount_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['discount_total', 'coupon_code'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
