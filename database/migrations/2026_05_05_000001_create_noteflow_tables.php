<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('color', 40)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('title', 255);
            $table->string('emoji', 32)->default('');
            $table->longText('content')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'folder_id']);
            $table->index(['user_id', 'is_favorite']);
        });

        Schema::create('note_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('type', 40);
            $table->longText('content')->nullable();
            $table->boolean('checked')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['note_id', 'sort_order']);
        });

        Schema::create('note_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->string('permission', 20)->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['note_id', 'created_at']);
        });

        Schema::create('note_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('emoji', 32)->nullable();
            $table->longText('content')->nullable();
            $table->json('blocks_snapshot')->nullable();
            $table->timestamps();

            $table->index(['note_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_note_id')->nullable()->constrained('notes')->nullOnDelete();
            $table->unsignedBigInteger('source_upload_id')->nullable();
            $table->string('tool', 40);
            $table->string('title', 255);
            $table->longText('input_text');
            $table->longText('output_text');
            $table->string('language', 80)->nullable();
            $table->string('tone', 40)->nullable();
            $table->string('output_length', 20)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tool', 'created_at']);
            $table->index(['source_note_id']);
            $table->index(['source_upload_id']);
        });

        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->string('status', 30)->default('processing');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('storage_path', 500)->nullable();
            $table->longText('summary')->nullable();
            $table->json('processing_options')->nullable();
            $table->string('output_format', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::table('ai_generations', function (Blueprint $table) {
            $table->foreign('source_upload_id')->references('id')->on('uploads')->nullOnDelete();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('message');
            $table->string('type', 40);
            $table->timestamp('scheduled_for');
            $table->boolean('email_enabled')->default(false);
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status', 'scheduled_for']);
            $table->index(['user_id', 'type']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('sent_to', 255);
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 40)->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sent_at']);
            $table->index(['notification_id']);
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('appearance')->nullable();
            $table->json('email_notifications')->nullable();
            $table->json('push_notifications')->nullable();
            $table->json('ai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('uploads');
        Schema::dropIfExists('note_versions');
        Schema::dropIfExists('note_shares');
        Schema::dropIfExists('note_blocks');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('folders');
    }
};
