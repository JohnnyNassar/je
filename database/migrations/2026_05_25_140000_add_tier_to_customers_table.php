<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a 'tier' to storefront customers so admins can categorise them
 * (regular / VIP / wholesale). Pairs with the loyalty system. Existing rows
 * default to 'regular'. Guarded with hasColumn so it is a safe no-op anywhere
 * the column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'tier')) {
                $table->string('tier')->default('regular')->after('points_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'tier')) {
                $table->dropColumn('tier');
            }
        });
    }
};
