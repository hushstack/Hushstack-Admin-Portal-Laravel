<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Branches Table
 *
 * Represents branches within a collection (e.g., main, develop, feature/*)
 * OOAD: Inverted Dependency - Branch belongs to Collection
 * Security: User-scoped data with status/stage tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            // Ownership - Security: Users only see their own branches
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Relationship to collection
            $table->foreignId('collection_id')
                ->constrained('collections')
                ->onDelete('cascade');

            // Branch metadata
            $table->string('name', 255);
            $table->text('description')->nullable();

            // Status tracking (pushed, merged, pending)
            $table->enum('status', ['pushed', 'merged', 'pending'])
                ->default('pending');

            // Stage tracking (local, dev, staging, pvt, prod)
            $table->enum('stage', ['local', 'dev', 'staging', 'pvt', 'prod'])
                ->default('local');

            // Timestamps for audit trail
            $table->timestamps();

            // Performance: Composite indexes for common queries
            $table->index(['user_id', 'collection_id']);
            $table->index(['user_id', 'stage']);
            $table->index(['collection_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
