<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->comment('방문자 통계');

            $table->id();
            $table->foreignId('post_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->ipAddress('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable()->comment('desktop, mobile, tablet');
            $table->string('browser')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
            $table->index(['ip_address', 'post_id', 'created_at']);
            $table->unique(['post_id', 'ip_address', 'user_id'], 'unique_view_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
