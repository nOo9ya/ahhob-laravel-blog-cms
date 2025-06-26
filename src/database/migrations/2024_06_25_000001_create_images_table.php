<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 이미지 테이블 마이그레이션
 * 
 * 이 마이그레이션은 업로드된 이미지의 메타데이터와 최적화 정보를 저장하는
 * images 테이블을 생성합니다.
 * 
 * 주요 기능:
 * - 다형성 관계를 통한 여러 모델과의 연결 (게시물, 사용자 등)
 * - 원본 이미지 및 썸네일 정보 저장
 * - 메타데이터 및 최적화 통계 관리
 * - 소프트 삭제 지원으로 데이터 복구 가능
 */
return new class extends Migration
{
    /**
     * 마이그레이션 실행 - images 테이블 생성
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 고유 식별자
            
            // 파일 기본 정보
            $table->string('original_name')->comment('원본 파일명'); // 사용자가 업로드한 파일의 원래 이름
            $table->string('file_name')->comment('저장된 파일명'); // 서버에 저장된 실제 파일명
            $table->string('file_path')->comment('파일 경로'); // storage/app/public 기준 상대 경로
            $table->string('disk', 50)->default('public')->comment('저장 디스크'); // 저장 위치 (public, s3 등)
            
            // 파일 속성
            $table->string('mime_type', 100)->comment('MIME 타입'); // image/jpeg, image/png 등
            $table->unsignedBigInteger('file_size')->comment('파일 크기 (바이트)'); // 원본 파일 크기
            $table->unsignedInteger('width')->comment('이미지 너비 (픽셀)'); // 원본 이미지 너비
            $table->unsignedInteger('height')->comment('이미지 높이 (픽셀)'); // 원본 이미지 높이
            
            // 썸네일 및 메타데이터 (JSON 저장)
            $table->json('thumbnails')->nullable()->comment('썸네일 정보 (JSON)'); // 생성된 썸네일들의 정보
            $table->json('metadata')->nullable()->comment('메타데이터 (JSON)'); // EXIF 데이터 등 추가 정보
            
            // 접근성 및 SEO
            $table->text('alt_text')->nullable()->comment('대체 텍스트 (접근성)'); // 시각 장애인을 위한 대체 텍스트
            $table->text('caption')->nullable()->comment('이미지 캡션'); // 이미지 설명
            
            // 다형성 관계 - 이미지가 어떤 모델에 속하는지 정의
            $table->string('imageable_type')->comment('연결된 모델 타입'); // App\Models\Blog\Post, App\Models\User 등
            $table->unsignedBigInteger('imageable_id')->comment('연결된 모델 ID'); // 해당 모델의 ID
            
            // 타임스탬프 및 소프트 삭제
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at - 소프트 삭제로 데이터 복구 가능
            
            // 인덱스 생성 - 성능 최적화
            $table->index(['imageable_type', 'imageable_id'], 'idx_images_morphs'); // 다형성 관계 쿼리 최적화
            $table->index('mime_type', 'idx_images_mime_type'); // MIME 타입별 검색 최적화
            $table->index('file_size', 'idx_images_file_size'); // 파일 크기별 정렬 최적화
            $table->index(['width', 'height'], 'idx_images_dimensions'); // 이미지 크기별 검색 최적화
            $table->index('created_at', 'idx_images_created_at'); // 날짜별 정렬 최적화
        });
    }

    /**
     * 마이그레이션 롤백 - images 테이블 삭제
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};