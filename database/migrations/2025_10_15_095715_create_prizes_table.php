<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('type', ['physical', 'promo_code']);
            $table->integer('point_cost');
            $table->integer('total_stock');
            $table->integer('available_stock')->default(0);
            $table->integer('blocked_stock')->default(0);
            $table->integer('used_stock')->default(0);
            $table->date('redemption_start_date');
            $table->date('redemption_end_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('banner_image')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
