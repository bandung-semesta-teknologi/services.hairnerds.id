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
        Schema::create('transaction_generals', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('service_type');
            $table->unsignedBigInteger('service_id');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method');
            $table->float('sub_total');
            $table->string('discount_code')->nullable();
            $table->float('discount_amount')->default(0);
            $table->float('tax_amount')->default(0);
            $table->string('voucher_code')->nullable();
            $table->float('voucher_amount')->default(0);
            $table->string('additional_charge_name')->nullable();
            $table->float('additional_charge_amount')->default(0);
            $table->float('total_amount');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_generals');
    }
};
