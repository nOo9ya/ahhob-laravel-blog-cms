<?php

use App\Http\Controllers\Ahhob\Blog\Admin\Common\ImageUploadController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ahhob\Blog\Admin\Auth\AuthController;
use App\Http\Controllers\Ahhob\Blog\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Ahhob\Blog\Admin\Post\PostController;
use App\Http\Controllers\Ahhob\Blog\Admin\Category\CategoryController;
use App\Http\Controllers\Ahhob\Blog\Admin\Page\PageController;
use App\Http\Controllers\Ahhob\Blog\Admin\Post\CommentController;
use App\Http\Controllers\Ahhob\Blog\Admin\User\UserController;

// 관리자 인증
Route::prefix('auth')->name('auth.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

// 관리자 전용 라우트
Route::middleware(['auth:admin'])->group(function () {
    // 대시보드
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');

    // 게시물 관리
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/create', [PostController::class, 'create'])->name('create');
        Route::post('/', [PostController::class, 'store'])->name('store');
        Route::get('/{post}', [PostController::class, 'show'])->name('show');
        Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
        Route::put('/{post}', [PostController::class, 'update'])->name('update');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
        Route::post('/{post}/restore', [PostController::class, 'restore'])->name('restore');
        Route::post('/bulk-action', [PostController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/upload-image', [PostController::class, 'uploadImage'])->name('upload-image');
        Route::post('/delete-image', [ImageUploadController::class, 'deleteImage'])->name('delete-image');
    });

    // 카테고리 관리
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
    });

    // 페이지 관리
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}', [PageController::class, 'show'])->name('show');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        Route::patch('/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('toggle-status');
    });

    // 댓글 관리
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('index');
        Route::get('/{comment}', [CommentController::class, 'show'])->name('show');
        Route::put('/{comment}/approve', [CommentController::class, 'approve'])->name('approve');
        Route::put('/{comment}/reject', [CommentController::class, 'reject'])->name('reject');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [CommentController::class, 'bulkAction'])->name('bulk-action');
    });

    // 사용자 관리
    Route::prefix('users')->name('users.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});
