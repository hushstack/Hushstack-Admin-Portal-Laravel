<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source', 80)->default('cachewraith');
            $table->string('server_name', 255)->index();
            $table->string('device_name', 255)->nullable();
            $table->string('type', 100)->index();
            $table->string('severity', 20)->index();
            $table->string('status', 30)->default('open')->index();
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('source_ip', 45)->nullable()->index();
            $table->string('user_name', 150)->nullable();
            $table->string('auth_method', 100)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->string('service_name', 150)->nullable();
            $table->string('path', 2048)->nullable();
            $table->string('fingerprint', 255)->index();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('first_seen_at')->index();
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['status', 'fingerprint']);
            $table->index(['severity', 'status', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
