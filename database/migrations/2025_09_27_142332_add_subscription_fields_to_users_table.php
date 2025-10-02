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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('mobile')->nullable()->after('email');
            $table->date('subscription_start_date')->nullable()->after('mobile');
            $table->date('subscription_end_date')->nullable()->after('subscription_start_date');
            $table->enum('role', ['super_admin', 'admin', 'user'])->default('user')->after('subscription_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'mobile', 'subscription_start_date', 'subscription_end_date', 'role']);
        });
    }
};
