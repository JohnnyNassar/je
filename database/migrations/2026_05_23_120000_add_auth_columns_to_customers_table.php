<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the customer-auth columns (email / password / verification / remember
 * token) that the Customer model and CustomerAuth controllers already rely on.
 *
 * The original 2026_05_20_104319 migration was left as an empty stub — the
 * columns only exist on the live servers because they were added by hand. This
 * migration makes the schema reproducible from code. Every column is guarded
 * with Schema::hasColumn(), so it is a safe no-op anywhere the columns already
 * exist (production) and adds them on a fresh database (local / new clones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'email')) {
                // Nullable: guest customers (created at checkout) have no email.
                // MariaDB allows multiple NULLs under a unique index.
                $table->string('email')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (! Schema::hasColumn('customers', 'password')) {
                // Nullable: a guest row has no password until the visitor registers.
                $table->string('password')->nullable()->after('email_verified_at');
            }
            if (! Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'email')) {
                $table->dropUnique('customers_email_unique');
                $table->dropColumn('email');
            }
            foreach (['email_verified_at', 'password', 'remember_token'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
