<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mirrors can_view_cost: a capability an administrator can be
            // refused without dropping them to the Staff tier, which would
            // also cost them customers, coupons, loyalty and the bazaar.
            if (! Schema::hasColumn('users', 'can_view_orders')) {
                $table->boolean('can_view_orders')->default(true)->after('can_view_cost');
            }
        });

        // can_view_cost used to be a grant that only meant anything for staff,
        // because isAdmin() short-circuited it. It is now authoritative for
        // everyone below the owner, so every existing admin has to be given
        // the flag explicitly or they would all silently lose cost access.
        DB::table('users')
            ->whereIn('role', ['admin', 'super_admin'])
            ->update(['can_view_cost' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_view_orders');
        });
    }
};
