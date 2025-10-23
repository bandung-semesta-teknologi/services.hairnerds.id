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
        Schema::create('service', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('gender')->nullable();
            $table->string('name_service')->nullable();
            $table->string('service_subtitle', 50)->nullable();
            $table->foreignId('id_category')->constrained('catalog_category');
            $table->text('description')->nullable();
            $table->string('youtube_code')->nullable();
            $table->smallInteger('price_type')->nullable();
            $table->string('price_description', 30)->nullable();
            $table->boolean('allow_visible')->nullable();
            $table->time('session_duration')->nullable();
            $table->time('buffer_time')->nullable();
            $table->string('image', 50)->nullable();
            $table->foreignId('id_store')->constrained('store');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service');
    }
};
