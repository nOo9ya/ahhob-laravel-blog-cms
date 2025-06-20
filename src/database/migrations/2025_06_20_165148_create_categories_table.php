<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->comment('blog categories');

            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6366f1')
                ->comment('HEX 색상');
            $table->string('icon')->nullable();

            // 하위 카테고리 지원을 위한 필드들
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->integer('depth')->default(0)
                ->comment('계층 깊이 (0: 최상위, 1: 1단계 하위, 2: 2단계 하위...)');
            $table->string('path')->nullable()
                ->comment('계층 경로 (예: "1/3/7")');
            $table->integer('children_count')->default(0)
                ->comment('직접 하위 카테고리 수');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // 인덱스 설정
            $table->index(['parent_id', 'is_active', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
            $table->index(['depth', 'sort_order']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
