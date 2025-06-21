<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Post\PostController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Comment\CommentController;

// 인증 라우트
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum')->name('user');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum')->name('profile.update');
});

// 공개 API
Route::prefix('posts')->name('posts.')->group(function () {
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/search', [PostController::class, 'search'])->name('search');
    Route::get('/featured', [PostController::class, 'featured'])->name('featured');
    Route::get('/{post:slug}', [PostController::class, 'show'])->name('show');

    // 인증 필요
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [PostController::class, 'store'])->name('store');
        Route::put('/{post}', [PostController::class, 'update'])->name('update');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/like', [PostController::class, 'like'])->name('like');
    });
});

// 카테고리 API
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{category:slug}', [CategoryController::class, 'show'])->name('show');
    Route::get('/{category:slug}/posts', [CategoryController::class, 'posts'])->name('posts');

    // 관리자 전용
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });
});

// 댓글 API
Route::prefix('comments')->name('comments.')->group(function () {
    Route::get('/post/{post}', [CommentController::class, 'byPost'])->name('by-post');

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/{comment}', [CommentController::class, 'update'])->name('update');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
        Route::post('/{comment}/like', [CommentController::class, 'like'])->name('like');
    });
});

// 태그 API
Route::get('/tags', [PostController::class, 'tags'])->name('tags');
Route::get('/tags/{tag:slug}/posts', [PostController::class, 'byTag'])->name('posts.by-tag');
