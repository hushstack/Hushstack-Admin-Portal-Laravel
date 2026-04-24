<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cli_login_requests', function (Blueprint $table) {
            $table->id();
            $table->string('device_code_hash', 64)->unique();
            $table->string('user_code_hash', 64)->unique();
            $table->string('status', 20)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('requested_abilities')->nullable();
            $table->string('client_name', 80)->nullable();
            $table->string('client_version', 40)->nullable();
            $table->string('device_name', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at'], 'cli_login_status_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cli_login_requests');
    }
};
