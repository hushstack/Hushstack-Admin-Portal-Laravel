<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('github_branches');
        Schema::dropIfExists('github_commits');
        Schema::dropIfExists('github_repositories');
    }

    public function down(): void
    {
        // Intentionally not recreating these tables; GitHub data is read directly from GitHub.
    }
};
