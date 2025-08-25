<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->datetime('start_at');
            $table->datetime('end_at');
            $table->integer('seat')->default(0);
            $table->integer('seat_available')->default(0);
            $table->integer('seat_blocked')->default(0);
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->enum('status', ['draft', 'publish', 'unpublish', 'rejected'])->default('draft');
            $table->integer('price')->default(0);
            $table->string('location');
            $table->string('contact_person');
            $table->string('url_location')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamps');
    }
};
