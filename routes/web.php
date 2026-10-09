<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DocumentSearchController;
use App\Http\Controllers\PortalCrudController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentPortalController;

Route::inertia('/', 'welcome')->name('home');

// Admin login page
Route::get('/admin/login', function () {
    return Inertia::render('auth/login', [
        'portal' => 'admin',
        'canResetPassword' => true,
        'status' => session('status'),
    ]);
})->middleware('guest')->name('admin.login');

// User dashboard
Route::middleware([
    'auth',
    'role:user',
])->group(function () {
    // Regular user dashboard
    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])
        ->middleware(['auth', 'role:user'])
        ->name('dashboard');
});

// Admin dashboard
Route::prefix('admin')
    ->name('admin.')
    ->middleware([
        'auth',
        'role:admin',
    ])
    ->group(function () {
        Route::get('/dashboard', [
            AdminDashboardController::class,
            'index',
        ])->name('dashboard');

        Route::resource('users', AdminUserController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
            ]);

        Route::get('/activity-logs', [
            ActivityLogController::class,
            'index',
        ])->name('activity-logs.index');
    });


Route::prefix('portal')
    ->name('portal.')
    ->middleware([
        'auth',
        'role:admin,user',
    ])
    ->group(function () {
        Route::get('/', [
            DocumentPortalController::class,
            'index',
        ])->name('index');

        Route::post('/folders', [
            DocumentPortalController::class,
            'storeFolder',
        ])->name('folders.store');

        Route::get('/folders/{folder}', [
            DocumentPortalController::class,
            'showFolder',
        ])->name('folders.show');

        Route::post('/folders/{folder}/documents', [
            DocumentPortalController::class,
            'storeDocument',
        ])->name('documents.store');

        Route::post('/folders/{folder}/shares', [
            DocumentPortalController::class,
            'shareFolder',
        ])->name('folders.share');

        Route::delete('/folders/{folder}/shares/{user}', [
            DocumentPortalController::class,
            'removeShare',
        ])->name('folders.shares.remove');

        Route::get('/documents/{document}/download', [
            DocumentPortalController::class,
            'download',
        ])->name('documents.download');

        Route::get('/documents/{document}/preview', [
            DocumentPortalController::class,
            'preview',
        ])->name('documents.preview');

        Route::post('/documents/{document}/versions', [
            DocumentPortalController::class,
            'uploadVersion',
        ])->name('documents.versions.store');

        Route::get(
            '/documents/{document}/versions/{version}/download',
            [
                DocumentPortalController::class,
                'downloadVersion',
            ],
        )->name('documents.versions.download');


        Route::get('/documents', [
            DocumentSearchController::class,
            'index',
        ])->name('documents.index');


        // Folder editing and deletion
        Route::get('/folders/{folder}/edit', [
            PortalCrudController::class,
            'editFolder',
        ])->name('folders.edit');

        Route::put('/folders/{folder}', [
            PortalCrudController::class,
            'updateFolder',
        ])->name('folders.update');

        Route::delete('/folders/{folder}', [
            PortalCrudController::class,
            'destroyFolder',
        ])->name('folders.destroy');

        // Document editing and deletion
        Route::get('/documents/{document}/edit', [
            PortalCrudController::class,
            'editDocument',
        ])->name('documents.edit');

        Route::put('/documents/{document}', [
            PortalCrudController::class,
            'updateDocument',
        ])->name('documents.update');

        Route::delete('/documents/{document}', [
            PortalCrudController::class,
            'destroyDocument',
        ])->name('documents.destroy');
    });

require __DIR__ . '/settings.php';
require __DIR__ . '/api.php';
