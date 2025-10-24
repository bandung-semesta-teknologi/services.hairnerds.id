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
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('id_store')->constrained('stores')->onDelete('cascade');
            $table->text('calendar_accesstoken')->nullable();
            $table->string('hairdnerds_calendarid')->nullable();
            $table->string('primary_calendarid')->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('characters')->nullable();
            $table->string('photo')->nullable();
            $table->string('email', 100);
            $table->string('hashtag')->nullable();
            $table->string('instagram', 50)->nullable();
            $table->string('facebook', 50)->nullable();
            $table->string('youtube_link')->nullable();
            $table->string('full_name', 80)->nullable();
            $table->text('background_desc')->nullable();
            $table->string('phone', 20)->nullable();
            $table->integer('is_active')->nullable();
            $table->string('color')->nullable();
            $table->integer('sync_status')->default(0)->nullable();
            $table->double('total_review')->default(0);
            $table->double('total_rating')->default(0);
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
        Schema::dropIfExists('barbers');
    }
};
