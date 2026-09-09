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
        Schema::table('events', function (Blueprint $table) {
            $table->index(['site_id', 'created_at', 'device_type'], 'events_site_id_created_at_device_type_index');
            $table->index(['site_id', 'created_at', 'country_code'], 'events_site_id_created_at_country_code_index');
            $table->index(['site_id', 'created_at', 'referrer'], 'events_site_id_created_at_referrer_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_site_id_created_at_device_type_index');
            $table->dropIndex('events_site_id_created_at_country_code_index');
            $table->dropIndex('events_site_id_created_at_referrer_index');
        });
    }
};
