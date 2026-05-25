<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index(); // model name or 'auth'
            $table->string('event')->nullable();             // created|updated|deleted|login|logout|failed_login
            $table->string('description');
            $table->string('subject_type')->nullable();      // the affected model
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();       // the user who did it
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();          // { old: {...}, attributes: {...} }
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
