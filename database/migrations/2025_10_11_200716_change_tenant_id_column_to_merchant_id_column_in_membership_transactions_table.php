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
        Schema::table('membership_transactions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
            $table->string('merchant_id')->after('transaction_general_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->after('transaction_general_id');
            $table->dropColumn('merchant_id');
        });
    }
};
