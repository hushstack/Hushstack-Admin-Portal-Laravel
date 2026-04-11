<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Collections Table
 *
 * Represents a project/repository (e.g., Ecommerce, Mobile App)
 * OOAD: Single Responsibility - Stores project metadata
 * Security: User-scoped data isolation (user_id foreign key)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            // Ownership - Security: Users only see their own collections
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Collection metadata
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();

            // Timestamps for audit trail
            $table->timestamps();

            // Performance: Index for user-scoped queries
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
