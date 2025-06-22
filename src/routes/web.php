<?php

use App\Http\Controllers\Ahhob\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Ahhob\Web\Auth\NewPasswordController;
use App\Http\Controllers\Ahhob\Web\Auth\PasswordResetLinkController;
use App\Http\Controllers\Ahhob\Web\Auth\ProfileController;
use App\Http\Controllers\Ahhob\Web\Auth\RegisteredUserController;
use App\Http\Controllers\Ahhob\Web\Blog\HomeController;
use App\Http\Controllers\Ahhob\Web\Blog\Post\PostController;
use App\Http\Controllers\Ahhob\Web\Blog\Post\PostLikeController;
use App\Http\Controllers\Ahhob\Web\Blog\Post\PostSearchController;
use App\Http\Controllers\Ahhob\Web\Blog\Category\CategoryController;
use App\Http\Controllers\Ahhob\Web\Blog\Comment\CommentController;
use Illuminate\Support\Facades\Route;

// 홈 및 기본 페이지
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.send');

// 인증 라우트
// 인증 관련 라우트
Route::prefix('auth')->name('auth.')->group(function () {

    // GUEST (로그인하지 않은 사용자만 접근 가능)
    Route::middleware('guest')->group(function () {
        // 회원가입
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);

        // 로그인
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        // 비밀번호 재설정 요청
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

        // 새 비밀번호 설정
        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    });

    // AUTH (로그인한 사용자만 접근 가능)
    Route::middleware('auth')->group(function () {
        // 로그아웃
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        // 프로필
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

// 블로그 포스트 관련 라우트
Route::prefix('posts')->name('posts.')->group(function () {
    // 포스트 목록 및 상세 보기
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/{post:slug}', [PostController::class, 'show'])->name('show')->middleware('track.visitor');

    // 포스트 검색 (별도 페이지)
    Route::get('/search', PostSearchController::class)->name('search');

    // 포스트 좋아요
    Route::post('/{post}/like', PostLikeController::class)
        ->middleware('auth') // 로그인한 사용자만 가능
        ->name('like');
});

// 태그별 포스트 목록 라우트
Route::get('/tags/{tag:slug}', PostByTagController::class)->name('posts.by-tag');

// 카테고리 라우트
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{category:slug}', [CategoryController::class, 'show'])->name('show');
});

// 댓글 라우트
Route::prefix('comments')->name('comments.')->middleware('throttle:10,1')->group(function () {
    Route::post('/', [CommentController::class, 'store'])->name('store')->middleware(['throttle:10,1', 'anti.spam']);
    Route::post('/{comment}/like', [CommentController::class, 'like'])->name('like')->middleware('auth');
});



// RSS 피드
Route::get('/feed', [HomeController::class, 'feed'])->name('feed');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
