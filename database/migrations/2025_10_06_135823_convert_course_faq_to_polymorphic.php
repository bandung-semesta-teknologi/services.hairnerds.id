<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_faqs', function (Blueprint $table) {
            $table->string('faqable_type')->after('id')->nullable();
            $table->unsignedBigInteger('faqable_id')->after('faqable_type')->nullable();
            $table->index(['faqable_type', 'faqable_id']);
        });

        DB::table('course_faqs')->update([
            'faqable_type' => 'App\\Models\\Course',
            'faqable_id' => DB::raw('course_id')
        ]);

        Schema::table('course_faqs', function (Blueprint $table) {
            $table->string('faqable_type')->nullable(false)->change();
            $table->unsignedBigInteger('faqable_id')->nullable(false)->change();

            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        Schema::rename('course_faqs', 'faqs');
    }

    public function down(): void
    {
        Schema::rename('faqs', 'course_faqs');

        Schema::table('course_faqs', function (Blueprint $table) {
            $table->foreignId('course_id')->after('id')->nullable()->constrained('courses')->cascadeOnDelete();
        });

        DB::table('course_faqs')
            ->where('faqable_type', 'App\\Models\\Course')
            ->update(['course_id' => DB::raw('faqable_id')]);

        Schema::table('course_faqs', function (Blueprint $table) {
            $table->dropIndex(['faqable_type', 'faqable_id']);
            $table->dropColumn(['faqable_type', 'faqable_id']);
        });
    }
};
