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
        Schema::create('membership_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_general_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('membership_code');
            $table->string('name');
            $table->string('email');
            $table->string('address');
            $table->string('phone_number');
            $table->integer('total_amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_transactions');
    }
};
