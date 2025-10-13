<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('short_biography')->nullable()->after('avatar');
            $table->text('biography')->nullable()->after('short_biography');
            $table->json('skills')->nullable()->after('biography');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['short_biography', 'biography', 'skills']);
        });
    }
};
