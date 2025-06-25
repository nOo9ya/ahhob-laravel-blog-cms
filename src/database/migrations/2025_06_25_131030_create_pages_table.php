<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->comment('페이지를 생성한 관리자 ID')->constrained()->onDelete('cascade');

            // 기본 콘텐츠 필드
            $table->string('title')
                ->comment('페이지 제목 (H1 태그로 사용될 수 있음)');
            $table->string('slug')->unique()
                ->comment('페이지의 고유 URL 경로');
            $table->longText('content')
                ->comment('페이지 본문 내용 (HTML 또는 마크다운)');
            $table->longText('content_html')->nullable();

            // 상태 및 게시일 관리
            $table->text('excerpt')->nullable();
            $table->string('status', 20)->default('draft')->index()
                ->comment('페이지 상태 (draft, published, archived)');
            $table->timestamp('published_at')->nullable()
                ->comment('페이지가 공개적으로 게시된 시간');

            // 표준 SEO 필드
            $table->string('meta_title')->nullable()
                ->comment('검색 엔진 결과에 표시될 제목 (title 태그)');
            $table->string('meta_description', 255)->nullable()
                ->comment('검색 엔진 결과에 표시될 설명 (meta description 태그)');
            $table->text('keywords')->nullable()
                ->comment('페이지 관련 키워드 (쉼표로 구분)');

            // 소셜 공유 (Open Graph) SEO 필드
            $table->string('og_title')->nullable()
                ->comment('Open Graph 제목 (소셜 공유 시 표시될 제목)');
            $table->string('og_description')->nullable()
                ->comment('Open Graph 설명 (소셜 공유 시 표시될 설명)');
            $table->string('og_image')->nullable()
                ->comment('Open Graph 이미지 URL (소셜 공유 시 표시될 이미지)');

            // 고급 SEO 필드
            $table->string('canonical_url')->nullable()
                ->comment('중복 콘텐츠 방지를 위한 표준 URL');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
