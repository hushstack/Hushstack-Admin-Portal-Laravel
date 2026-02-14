<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('execute_at');
            $table->timestamps();

            $table->index(['execute_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletions');
    }
};
