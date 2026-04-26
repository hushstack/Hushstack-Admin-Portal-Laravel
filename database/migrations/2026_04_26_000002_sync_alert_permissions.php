<?php

use App\Services\PermissionRegistrar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->syncPermissions();
    }

    public function down(): void
    {
        // Keep permission rows intact to avoid removing assigned production permissions.
    }
};
