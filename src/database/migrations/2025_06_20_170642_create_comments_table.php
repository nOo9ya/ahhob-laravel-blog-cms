<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->comment('blog comments');

            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained()->nullOnDelete(); // 회원 댓글

            // 비회원 댓글 정보
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_website')->nullable();

            $table->text('content');
            $table->foreignId('parent_id')->nullable()
                ->constrained('comments')->cascadeOnDelete(); // 대댓글
            $table->integer('depth')->default(0)
                ->comment('댓글 깊이 (0: 원댓글, 1: 대댓글)');
            $table->string('path')->nullable()
                ->comment('댓글 경로 (예: "1/3/7")');

            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])
                ->default('pending');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();

            $table->integer('likes_count')->default(0);
            $table->integer('replies_count')->default(0)
                ->comment('대댓글 수');

            $table->boolean('is_pinned')->default(false)
                ->comment('고정 댓글');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'status', 'created_at']);
            $table->index(['parent_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
