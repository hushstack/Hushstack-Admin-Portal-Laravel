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
        Schema::table('user_activities', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->dropColumn(['first_name', 'last_name', 'username', 'email', 'role_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('username', 120)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->string('role_name', 80)->nullable();
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['user_id', 'role_id']);
        });
    }
};
