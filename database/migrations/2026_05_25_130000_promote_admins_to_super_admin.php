<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Introduces the three-tier role model. Previously 'admin' was the owner with
 * full access; now the owner tier is 'super_admin' and a new lesser 'admin'
 * tier sits below it (everyday ops, but no user/settings/activity-log access).
 * Existing 'admin' rows are the current owners, so promote them all to
 * 'super_admin' to preserve their access. Reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
    }
};
