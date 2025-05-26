<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlatfromController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ToggleUserPlatformController;
use App\Http\Controllers\UploadTemporatyAttachmentsController;
use App\Models\Post;
use App\Services\PlatformService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('dashboard', HomeController::class)->name('dashboard');

    Route::resource('posts', PostController::class)->only('index', 'create', 'store')->names('posts');

    // Made this routes to solve route model binding issue becuase there is a globalScope that prevents the binding to work as expected
    Route::prefix('posts')->group(function () {
        Route::get('/{post}', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });


    Route::get('platforms', [PlatfromController::class, 'index'])->name('platform.index');
    Route::post('toggle/platform', ToggleUserPlatformController::class)->name('platform.toggle');
});

// Uploading Attachments
Route::post('/attachments/upload', [UploadTemporatyAttachmentsController::class, 'uploadAttachments'])->name('upload');
Route::post('/attachments/revert', [UploadTemporatyAttachmentsController::class, 'revertAttachments'])->name('revert');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';