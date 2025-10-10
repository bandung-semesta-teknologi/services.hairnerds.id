<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'instructor', 'student') NOT NULL");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_temp')->after('role');
            });

            DB::table('users')->update(['role_temp' => DB::raw('role')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->after('password');
            });

            DB::table('users')->update(['role' => DB::raw('role_temp')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role_temp');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'instructor', 'student') NOT NULL");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_temp')->after('role');
            });

            DB::table('users')->update(['role_temp' => DB::raw('role')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->after('password');
            });

            DB::table('users')->update(['role' => DB::raw('role_temp')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role_temp');
            });
        }
    }
};
