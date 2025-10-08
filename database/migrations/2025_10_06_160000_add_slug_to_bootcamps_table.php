<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bootcamps', function (Blueprint $table) {
            if (!Schema::hasColumn('bootcamps', 'slug')) {
                $table->string('slug')->after('title')->nullable();
                $table->index('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bootcamps', function (Blueprint $table) {
            if (Schema::hasColumn('bootcamps', 'slug')) {
                $table->dropIndex(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};


