<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'User',    'slug' => 'user',    'description' => 'Default user role', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Member',  'slug' => 'member',  'description' => 'Member role', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Partner', 'slug' => 'partner', 'description' => 'Partner role', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Admin',   'slug' => 'admin',   'description' => 'Administrator role', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
