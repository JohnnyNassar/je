<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reworks the bazaar around the real commercial terms:
 *
 *  - 30 JOD buys a WEEKEND (Thursday + Friday together), not a single night.
 *    Nights are kept as records — they carry the trading hours and leave the
 *    door open to selling single days later — but the unit a vendor books is
 *    now a period.
 *  - A refundable deposit is taken per booking.
 *  - Vendors declare a category; food categories must supply a health
 *    certificate, and a work/trade certificate is collected from everyone.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A bookable period. Today that is one weekend; the same table can hold
        // a single day or a whole month if the commercial model changes.
        if (! Schema::hasTable('bazaar_periods')) {
            Schema::create('bazaar_periods', function (Blueprint $table) {
                $table->id();
                $table->date('starts_on');
                $table->date('ends_on');
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['starts_on', 'ends_on']);
                $table->index(['is_active', 'starts_on']);
            });
        }

        if (! Schema::hasColumn('bazaar_nights', 'bazaar_period_id')) {
            Schema::table('bazaar_nights', function (Blueprint $table) {
                $table->foreignId('bazaar_period_id')->nullable()->after('id')
                    ->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('bazaar_vendor_categories')) {
            Schema::create('bazaar_vendor_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name_en');
                $table->string('name_ar')->nullable();
                $table->string('slug')->unique();
                // Food, drink and anything ingested or applied to the body needs
                // a health certificate before it may trade (contract Art. 31).
                $table->boolean('requires_health_certificate')->default(false);
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'position']);
            });
        }

        Schema::table('bazaar_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bazaar_bookings', 'bazaar_period_id')) {
                $table->foreignId('bazaar_period_id')->nullable()->after('id')
                    ->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('bazaar_bookings', 'bazaar_vendor_category_id')) {
                $table->foreignId('bazaar_vendor_category_id')->nullable()
                    ->after('customer_id')->nullOnDelete();
            }
            if (! Schema::hasColumn('bazaar_bookings', 'deposit')) {
                $table->decimal('deposit', 10, 2)->default(0)->after('price');
            }
            if (! Schema::hasColumn('bazaar_bookings', 'deposit_returned_at')) {
                $table->timestamp('deposit_returned_at')->nullable()->after('deposit');
            }
        });

        // Re-point the double-booking guard from night to period. Safe to drop
        // and rebuild: this ships before any booking has been taken.
        //
        // Order matters. MariaDB uses the unique index to satisfy the foreign
        // key on bazaar_night_id, and refuses to drop an index a constraint
        // still needs -- so the FK has to go first, then the index, then the
        // column. Each step is guarded so a partially-applied run can resume.
        if ($this->foreignKeyExists('bazaar_bookings', 'bazaar_bookings_bazaar_night_id_foreign')) {
            Schema::table('bazaar_bookings', function (Blueprint $table) {
                $table->dropForeign('bazaar_bookings_bazaar_night_id_foreign');
            });
        }

        if ($this->indexExists('bazaar_bookings', 'bazaar_bookings_slot_unique')) {
            Schema::table('bazaar_bookings', function (Blueprint $table) {
                $table->dropUnique('bazaar_bookings_slot_unique');
            });
        }

        if (Schema::hasColumn('bazaar_bookings', 'bazaar_night_id')) {
            Schema::table('bazaar_bookings', function (Blueprint $table) {
                $table->dropColumn('bazaar_night_id');
            });
        }

        if (! $this->indexExists('bazaar_bookings', 'bazaar_bookings_slot_unique')) {
            Schema::table('bazaar_bookings', function (Blueprint $table) {
                $table->unique(
                    ['bazaar_period_id', 'bazaar_table_id', 'active_slot'],
                    'bazaar_bookings_slot_unique'
                );
            });
        }

        // Certificates and licences. Stored on the PRIVATE disk, never under
        // public/ — these carry personal and business data.
        if (! Schema::hasTable('bazaar_booking_documents')) {
            Schema::create('bazaar_booking_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bazaar_booking_id')->constrained()->cascadeOnDelete();
                $table->string('kind', 16);            // work | health
                $table->string('path');                // relative to the local (private) disk
                $table->string('original_name');
                $table->string('mime', 100)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->timestamps();

                $table->index(['bazaar_booking_id', 'kind']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        )->c > 0;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
             WHERE table_schema = DATABASE() AND table_name = ?
               AND constraint_name = ? AND constraint_type = ?',
            [$table, $constraint, 'FOREIGN KEY']
        )->c > 0;
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_booking_documents');

        Schema::table('bazaar_bookings', function (Blueprint $table) {
            $table->dropUnique('bazaar_bookings_slot_unique');
        });

        Schema::table('bazaar_bookings', function (Blueprint $table) {
            $table->foreignId('bazaar_night_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::table('bazaar_bookings', function (Blueprint $table) {
            $table->unique(
                ['bazaar_night_id', 'bazaar_table_id', 'active_slot'],
                'bazaar_bookings_slot_unique'
            );
            $table->dropConstrainedForeignId('bazaar_period_id');
            $table->dropConstrainedForeignId('bazaar_vendor_category_id');
            $table->dropColumn(['deposit', 'deposit_returned_at']);
        });

        Schema::table('bazaar_nights', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bazaar_period_id');
        });

        Schema::dropIfExists('bazaar_vendor_categories');
        Schema::dropIfExists('bazaar_periods');
    }
};
