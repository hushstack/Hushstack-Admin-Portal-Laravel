<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Projects Table Migration
 *
 * Creates the projects table with all required fields and indexes.
 *
 * Performance: Includes indexes for frequently queried columns
 * Security: Uses appropriate column types and constraints
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();

            // Core fields
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('url', 255)->nullable();

            // JSON array for technologies
            $table->json('technologies')->nullable();

            // Status field
            $table->boolean('is_published')->default(false);

            // Timestamps
            $table->timestamps();

            // Soft deletes for data recovery
            $table->softDeletes();

            // Indexes for performance
            $table->index('is_published', 'idx_projects_published');
            $table->index('created_at', 'idx_projects_created_at');
            $table->index(['is_published', 'created_at'], 'idx_projects_published_created');

            // Full-text search index (if using MySQL 5.6+ or PostgreSQL)
            // $table->fullText(['title', 'description'], 'idx_projects_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
