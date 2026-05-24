<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('multiplier'); // multiplier | bonus
            $table->decimal('multiplier', 6, 2)->nullable(); // e.g. 2.00 = double points
            $table->integer('bonus_points')->nullable();      // flat points added per order
            $table->decimal('min_order_total', 10, 2)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_promotions');
    }
};
