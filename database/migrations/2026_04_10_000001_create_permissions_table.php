<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            // OWASP A01:2021 - Use strict string limits to prevent buffer overflow attacks
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            // Performance: Index for faster lookups by slug
            $table->index('slug', 'idx_permissions_slug');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
