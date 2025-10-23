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
        Schema::create('service_barber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_service')->constrained('service')->onDelete('cascade');
            $table->foreignId('id_barber')->constrained('barber')->onDelete('cascade');
            $table->double('price')->nullable();
            $table->double('weekend_price')->nullable()->nullable();
            $table->smallInteger('status')->default(0)->nullable();
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
        Schema::dropIfExists('service_barber');
    }
};
