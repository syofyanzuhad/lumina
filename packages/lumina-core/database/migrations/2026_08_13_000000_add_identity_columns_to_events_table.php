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
            $table->string('visitor_id', 100)->nullable()->after('visitor_hash');
            $table->string('session_id', 100)->nullable()->after('visitor_id');
            $table->uuid('event_id')->nullable()->after('session_id');

            $table->index(['site_id', 'visitor_id', 'created_at'], 'events_site_visitor_created_idx');
            $table->index(['site_id', 'session_id', 'created_at'], 'events_site_session_created_idx');
            $table->unique('event_id', 'events_event_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_event_id_unique');
            $table->dropIndex('events_site_visitor_created_idx');
            $table->dropIndex('events_site_session_created_idx');
            $table->dropColumn(['visitor_id', 'session_id', 'event_id']);
        });
    }
};
