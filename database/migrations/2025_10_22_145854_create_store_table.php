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
        Schema::create('store', function (Blueprint $table) {
            $table->id();
            $table->string('store_name', 25)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('picture');
            $table->text('website')->nullable();
            $table->uuid('id_owner');
            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_twitter')->nullable();
            $table->boolean('is_active')->default(false)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->double('delivery_charge')->default(0)->nullable();
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
        Schema::dropIfExists('store');
    }
};
