<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name');
            $table->morphs('payable');
            $table->string('payment_code')->unique();
            $table->string('payment_method');
            $table->string('payment_url')->nullable();
            $table->integer('amount')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total')->default(0);
            $table->string('midtrans_transaction_id')->nullable();
            $table->json('raw_response_midtrans')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'canceled'])->default('pending');
            $table->datetime('paid_at')->nullable();
            $table->datetime('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
