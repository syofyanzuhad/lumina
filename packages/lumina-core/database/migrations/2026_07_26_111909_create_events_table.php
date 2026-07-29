<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('path');
            $table->string('referrer')->nullable();
            $table->string('visitor_hash', 64);
            $table->string('device_type', 20)->default('unknown');
            $table->string('country')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['site_id', 'visitor_hash', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
