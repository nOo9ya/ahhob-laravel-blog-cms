<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('role')->default('user')->after('email'); // user, admin, writer
            $table->string('avatar')->nullable()->after('role');
            $table->text('bio')->nullable()->after('avatar');
            $table->string('website')->nullable()->after('bio');
            $table->string('social_twitter')->nullable()->after('website');
            $table->string('social_github')->nullable()->after('social_twitter');
            $table->string('social_linkedin')->nullable()->after('social_github');
            $table->boolean('is_active')->default(true)->after('social_linkedin');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'role', 'avatar', 'bio', 'website',
                'social_twitter', 'social_github', 'social_linkedin',
                'is_active', 'last_login_at'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
