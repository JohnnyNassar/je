<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'can_view_cost')) {
                // Grants a Staff member access to cost prices & profit without
                // promoting them to the admin tier. Admins always see cost.
                $table->boolean('can_view_cost')->default(false)->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_view_cost')) {
                $table->dropColumn('can_view_cost');
            }
        });
    }
};
