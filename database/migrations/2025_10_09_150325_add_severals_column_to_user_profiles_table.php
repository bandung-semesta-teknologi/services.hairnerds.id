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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->uuid('user_uuid_supabase')->nullable()->after('user_id');
            $table->string('serial_number')->unique()->nullable()->after('user_uuid_supabase');
            $table->string('card_number')->nullable()->after('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropUnique(['serial_number']);
            $table->dropColumn(['user_uuid_supabase', 'card_number', 'serial_number']);
        });
    }
};
