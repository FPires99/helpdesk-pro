<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CannedResponseController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/tickets/export', [TicketController::class, 'export'])->middleware('staff')->name('tickets.export');
    Route::resource('tickets', TicketController::class)->except(['edit']);
    Route::post('/tickets/{ticket}/assign-to-me', [TicketController::class, 'assignToMe'])->name('tickets.assign-to-me');
    Route::post('/tickets/{ticket}/rating', [TicketController::class, 'rate'])->name('tickets.rate');
    Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store'])->name('tickets.comments.store');

    Route::get('/kb/suggest', [ArticleController::class, 'suggest'])->name('articles.suggest');
    Route::post('/kb/{article}/helpful', [ArticleController::class, 'helpful'])->name('articles.helpful');
    Route::resource('kb', ArticleController::class)->parameters(['kb' => 'article'])->names('articles');

    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

    Route::middleware('staff')->group(function () {
        Route::get('/reports', ReportController::class)->name('reports');
        Route::resource('saved-replies', CannedResponseController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['saved-replies' => 'cannedResponse'])
            ->names('canned-responses');
    });

    Route::middleware('staff:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('demo.protect')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('demo.protect')->name('profile.destroy');
    Route::post('/profile/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/profile/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
});

require __DIR__.'/auth.php';
