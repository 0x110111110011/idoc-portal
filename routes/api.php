<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController;

Route::middleware('auth')->group(function () {
    // Folders
    Route::get('/folders', [
        FolderController::class,
        'index',
    ]);

    Route::post('/folders', [
        FolderController::class,
        'store',
    ]);

    Route::get('/folders/{folder}', [
        FolderController::class,
        'show',
    ]);

    Route::put('/folders/{folder}', [
        FolderController::class,
        'update',
    ]);

    Route::delete('/folders/{folder}', [
        FolderController::class,
        'destroy',
    ]);

    // Folder sharing
    Route::post('/folders/{folder}/share', [
        FolderController::class,
        'share',
    ]);

    Route::delete('/folders/{folder}/share/{user}', [
        FolderController::class,
        'removeShare',
    ]);

    // Documents
    Route::get('/folders/{folder}/documents', [
        DocumentController::class,
        'index',
    ]);

    Route::post('/folders/{folder}/documents', [
        DocumentController::class,
        'store',
    ]);

    Route::get('/documents/{document}', [
        DocumentController::class,
        'show',
    ]);

    Route::put('/documents/{document}', [
        DocumentController::class,
        'update',
    ]);

    Route::delete('/documents/{document}', [
        DocumentController::class,
        'destroy',
    ]);

    Route::get('/documents/{document}/download', [
        DocumentController::class,
        'download',
    ]);

    Route::post('/documents/{document}/versions', [
        DocumentController::class,
        'uploadVersion',
    ]);

    Route::get('/activity-logs', [
        ActivityLogController::class,
        'index',
    ]);
});
