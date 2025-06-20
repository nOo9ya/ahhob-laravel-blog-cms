<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->comment('blog tags');

            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6b7280')
                ->comment('HEX 색상');
            $table->integer('posts_count')->default(0)
                ->comment('해당 태그를 사용하는 포스트 수');
            $table->boolean('is_featured')->default(false)
                ->comment('추천 태그');
            $table->timestamps();

            $table->index(['posts_count', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
