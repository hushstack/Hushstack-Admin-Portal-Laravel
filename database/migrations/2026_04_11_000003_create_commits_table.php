<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Commits Table
 *
 * Represents commits within a branch
 * OOAD: Commit belongs to Branch, Branch belongs to Collection
 * Security: User-scoped data with SHA tracking for integrity
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commits', function (Blueprint $table) {
            $table->id();

            // Ownership - Security: Users only see their own commits
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Relationship to branch
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->onDelete('cascade');

            // Commit metadata
            $table->string('name', 255);
            $table->text('description')->nullable();

            // Git SHA for integrity verification (40 chars for SHA-1)
            $table->string('sha', 64)->nullable();

            // Timestamps for audit trail
            $table->timestamps();

            // Performance: Composite indexes for common queries
            $table->index(['user_id', 'branch_id']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};
