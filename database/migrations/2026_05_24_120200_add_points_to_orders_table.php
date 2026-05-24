<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'points_earned')) {
                $table->integer('points_earned')->default(0)->after('discount_total');
            }
            if (! Schema::hasColumn('orders', 'points_redeemed')) {
                $table->integer('points_redeemed')->default(0)->after('points_earned');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['points_earned', 'points_redeemed'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
