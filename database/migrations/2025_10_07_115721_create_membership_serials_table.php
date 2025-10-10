<?php

use App\Enums\MembershipType;
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
        Schema::create('membership_serials', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('card_number')->nullable();
            $table->string('type')->default(MembershipType::Regular->value); // premium, reguler
            $table->tinyInteger('is_used')->default(0); // 0 = not used, 1 = used
            $table->uuid('used_by')->nullable();
            $table->datetime('used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_serials');
    }
};
