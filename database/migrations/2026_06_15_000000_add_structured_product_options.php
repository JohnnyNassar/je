<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The axes a product is sold by — e.g. Colour, Size, Dimension (max 3,
        // enforced in the form). Each option carries its allowed values as JSON.
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->json('values')->nullable();   // [{ "en": "Red", "ar": "أحمر" }, ...]
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Each variant is one combination. option_values maps each option's English
        // name to the chosen English value, e.g. {"Colour":"Red","Size":"M"}. Legacy
        // free-text variants leave this null and keep working as a flat option list.
        Schema::table('product_variants', function (Blueprint $table) {
            $table->json('option_values')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('option_values');
        });

        Schema::dropIfExists('product_options');
    }
};
