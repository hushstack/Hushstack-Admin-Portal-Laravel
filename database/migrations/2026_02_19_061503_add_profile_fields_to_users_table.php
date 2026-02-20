<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Social links
            $table->string('facebook_url')->nullable()->after('contact_url');
            $table->string('x_url')->nullable()->after('facebook_url');          // X.com (Twitter)
            $table->string('linkedin_url')->nullable()->after('x_url');
            $table->string('instagram_url')->nullable()->after('linkedin_url');

            // Address details for the “Edit Address” modal
            $table->string('country')->nullable()->after('address');
            $table->string('city_state')->nullable()->after('country');
            $table->string('postal_code', 50)->nullable()->after('city_state');
            $table->string('tax_id', 100)->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url',
                'x_url',
                'linkedin_url',
                'instagram_url',
                'country',
                'city_state',
                'postal_code',
                'tax_id',
            ]);
        });
    }
};
