<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Home\HomeController;
use App\Http\Controllers\Web\Post\PostController;
use App\Http\Controllers\Web\Category\CategoryController;
use App\Http\Controllers\Web\Comment\CommentController;
use App\Http\Controllers\Web\Auth\AuthController;

// 홈 및 기본 페이지
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.send');

// 인증 라우트
Route::prefix('auth')->name('auth.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('forgot-password.submit');
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password.submit');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    });
});

// 게시물 라우트
Route::prefix('posts')->name('posts.')->group(function () {
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/search', [PostController::class, 'search'])->name('search');
    Route::get('/{post:slug}', [PostController::class, 'show'])->name('show')->middleware('track.visitor');
    Route::post('/{post}/like', [PostController::class, 'like'])->name('like')->middleware('auth');
});

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

// 태그 라우트
Route::get('/tags/{tag:slug}', [PostController::class, 'byTag'])->name('posts.by-tag');

// RSS 피드
Route::get('/feed', [HomeController::class, 'feed'])->name('feed');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
