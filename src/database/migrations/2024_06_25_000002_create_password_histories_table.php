<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 비밀번호 히스토리 테이블 마이그레이션
 * 
 * 이 마이그레이션은 사용자의 이전 비밀번호들을 저장하는 테이블을 생성합니다.
 * 비밀번호 재사용 방지 정책을 위해 사용되며, 해시된 비밀번호만 저장됩니다.
 * 
 * 보안 기능:
 * - 이전 N개의 비밀번호 재사용 방지
 * - 해시된 값만 저장으로 원본 비밀번호 보호
 * - 자동 정리 기능으로 오래된 히스토리 관리
 * - 사용자별 인덱스로 빠른 조회 성능
 */
return new class extends Migration
{
    /**
     * 마이그레이션 실행 - password_histories 테이블 생성
     */
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 고유 식별자
            
            // 사용자 관계
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('비밀번호 소유자 사용자 ID');
            
            // 비밀번호 정보
            $table->string('password_hash')
                ->comment('해시된 이전 비밀번호');
            
            // 타임스탬프 (created_at만 사용, updated_at은 불필요)
            $table->timestamp('created_at')
                ->useCurrent()
                ->comment('비밀번호 생성 일시');
            
            // 인덱스 생성 - 성능 최적화
            $table->index(['user_id', 'created_at'], 'idx_password_histories_user_date');
            $table->index('created_at', 'idx_password_histories_created_at');
        });

        // 테이블 코멘트 추가
        DB::statement("ALTER TABLE password_histories COMMENT = '사용자 비밀번호 히스토리 - 비밀번호 재사용 방지를 위한 이전 비밀번호 저장'");
    }

    /**
     * 마이그레이션 롤백 - password_histories 테이블 삭제
     */
    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};