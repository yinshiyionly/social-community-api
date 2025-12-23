<?php

use App\Http\Controllers\FileUploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| File Upload API Routes
|--------------------------------------------------------------------------
|
| These routes handle file upload operations including single file upload,
| batch upload, file management, and file information retrieval.
| All routes require authentication and appropriate permissions.
|
*/

// All file upload routes require authentication and operation logging
Route::middleware(['system.auth'])->group(function () {

    // File Upload Routes
    Route::prefix('files')->name('files.')->group(function () {

        // Single file upload
        Route::post('/upload', [FileUploadController::class, 'upload'])
            ->middleware(['permission:system:file:upload'])
            ->name('upload');

        // Multiple files upload
        Route::post('/upload-multiple', [FileUploadController::class, 'uploadMultiple'])
            ->middleware(['permission:system:file:upload'])
            ->name('upload.multiple');

        // File information and management
        Route::get('/', [FileUploadController::class, 'index'])
            ->middleware('permission:system:file:list')
            ->name('index');

        Route::get('/{id}', [FileUploadController::class, 'show'])
            ->middleware('permission:system:file:query')
            ->name('show')
            ->where('id', '[0-9]+');

        Route::delete('/{id}', [FileUploadController::class, 'destroy'])
            ->middleware('permission:system:file:remove')
            ->name('destroy')
            ->where('id', '[0-9]+');

        // File download/access (public endpoint for authenticated users)
        Route::get('/{id}/download', [FileUploadController::class, 'download'])
            ->middleware('permission:system:file:download')
            ->name('download')
            ->where('id', '[0-9]+');

        // File statistics and management (must be before {id} routes to avoid conflicts)
        Route::get('/statistics/overview', [FileUploadController::class, 'statistics'])
            ->middleware('permission:system:file:statistics')
            ->name('statistics');

        // Batch operations
        Route::delete('/batch', [FileUploadController::class, 'batchDestroy'])
            ->middleware('permission:system:file:remove')
            ->name('batch.destroy');
    });
});
