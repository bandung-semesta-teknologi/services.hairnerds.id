<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('bootcamps', function (Blueprint $table) {
			if (!Schema::hasColumn('bootcamps', 'thumbnail')) {
				$table->string('thumbnail')->nullable()->after('short_description');
			}
		});
	}

	public function down(): void
	{
		Schema::table('bootcamps', function (Blueprint $table) {
			if (Schema::hasColumn('bootcamps', 'thumbnail')) {
				$table->dropColumn('thumbnail');
			}
		});
	}
};


