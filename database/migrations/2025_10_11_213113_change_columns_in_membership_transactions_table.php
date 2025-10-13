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
            $table->dropColumn(['tenant_id', 'transaction_general_id', 'membership_code']);
            $table->string('merchant_id')->after('id');
            $table->string('merchant_name')->after('merchant_id');
            $table->string('merchant_email')->after('merchant_name');
            $table->unsignedBigInteger('user_id')->after('merchant_email');
            $table->uuid('user_uuid_supabase')->after('user_id');
            $table->string('serial_number')->after('user_uuid_supabase');
            $table->string('card_number')->after('serial_number');
            $table->string('address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_general_id')->after('id');
            $table->unsignedBigInteger('tenant_id')->after('transaction_general_id');
            $table->string('membership_code')->after('tenant_id');
            $table->string('address')->nullable(false)->change();
            $table->dropColumn(['merchant_id', 'user_id', 'user_uuid_supabase', 'serial_number', 'card_number', 'merchant_name', 'merchant_email']);
        });
    }
};
