<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The event nights (Thursdays + Fridays, 23 Jul – 30 Oct 2026).
        if (! Schema::hasTable('bazaar_nights')) {
            Schema::create('bazaar_nights', function (Blueprint $table) {
                $table->id();
                $table->date('event_date')->unique();
                // Full datetimes rather than TIME columns: the bazaar runs until
                // midnight, so ends_at legitimately falls on the following day.
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'event_date']);
            });
        }

        // The 100 physical tables on the floor plan.
        if (! Schema::hasTable('bazaar_tables')) {
            Schema::create('bazaar_tables', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('number')->unique();
                // A / B / C / D, plus RESTAURANT for the 12 units along the top.
                $table->string('section', 16);
                $table->decimal('price', 10, 2)->default(30);
                // Geometry for the generated SVG floor plan (viewBox units).
                $table->smallInteger('pos_x');
                $table->smallInteger('pos_y');
                $table->smallInteger('width')->default(28);
                $table->smallInteger('height')->default(20);
                $table->smallInteger('rotation')->default(0);
                $table->boolean('is_active')->default(true);
                // Restaurant units are drawn on the plan for orientation but are
                // not vendor tables, so they are not offered for booking.
                $table->boolean('is_bookable')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'is_bookable']);
                $table->index(['section']);
            });
        }

        if (! Schema::hasTable('bazaar_bookings')) {
            Schema::create('bazaar_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bazaar_night_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bazaar_table_id')->constrained()->cascadeOnDelete();
                // Vendors are Customers (reused by phone), but a booking can be
                // taken for someone with no account at all.
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

                $table->string('vendor_name');
                $table->string('vendor_phone', 32);
                $table->string('vendor_business')->nullable();
                $table->text('goods_description')->nullable();

                $table->decimal('price', 10, 2);
                $table->string('status', 16)->default('pending'); // pending|confirmed|cancelled
                $table->text('notes')->nullable();

                // Double-booking guard. Holds 1 while the booking occupies the
                // slot and NULL once cancelled -- MariaDB treats NULLs as
                // distinct in a unique index, so cancelled rows are kept for
                // history while only ONE live booking per table-night is allowed.
                $table->unsignedTinyInteger('active_slot')->nullable()->default(1);

                $table->timestamps();

                $table->unique(
                    ['bazaar_night_id', 'bazaar_table_id', 'active_slot'],
                    'bazaar_bookings_slot_unique'
                );
                $table->index(['vendor_phone']);
                $table->index(['status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_bookings');
        Schema::dropIfExists('bazaar_tables');
        Schema::dropIfExists('bazaar_nights');
    }
};
