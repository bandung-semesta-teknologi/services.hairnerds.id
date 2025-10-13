<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bootcamps', function (Blueprint $table) {
            $table->text('url_location')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bootcamps', function (Blueprint $table) {
            $table->string('url_location', 255)->nullable()->change();
        });
    }
};
