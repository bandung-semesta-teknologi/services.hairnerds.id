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
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_category', 25)->nullable();
            $table->smallInteger('gender')->nullable();
            $table->smallInteger('status')->nullable();
            $table->integer('sequence');
            $table->string('image', 50);
            $table->foreignId('id_store')->constrained('stores')->onDelete('cascade');
            $table->integer('is_recommendation')->nullable();
            $table->boolean('is_distance_matter')->default(false)->nullable();
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
        Schema::dropIfExists('service_categories');
    }
};
