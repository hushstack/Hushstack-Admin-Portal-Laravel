<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('username')->unique();

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('phone_number')->unique();

            $table->string('password');
            $table->boolean('is_verified')->default(false);

            $table->string('picture')->nullable();
            $table->string('cover')->nullable();
            $table->string('bio')->nullable();
            $table->string('note')->nullable();

            $table->date('birth_of_date')->nullable();
            $table->string('age')->nullable();

            $table->unsignedBigInteger('nationality_id')->nullable();

            $table->string('contact_url')->nullable();
            $table->string('address')->nullable();

            $table->string('provider')->nullable();     // google|github|facebook
            $table->string('provider_id')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
