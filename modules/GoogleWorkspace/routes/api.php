<?php

use Illuminate\Support\Facades\Route;
use Modules\GoogleWorkspace\Http\Controllers\Api\GoogleSettingsController;
use Modules\GoogleWorkspace\Http\Controllers\Api\GoogleWorkspaceController;

Route::get('/google/callback', [GoogleWorkspaceController::class, 'handleGoogleCallback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/google/docs', [GoogleWorkspaceController::class, 'listDocs']);
    Route::get('/google/get-all-files', [GoogleWorkspaceController::class, 'listGoogleDriveFiles']);
    Route::get('/google/drive/{fileType}', [GoogleWorkspaceController::class, 'getFiles']);
    Route::get('/google/{type}/preview/{documentId}', [GoogleWorkspaceController::class, 'proxyDocument']);
    Route::get('/google/auth', [GoogleWorkspaceController::class, 'redirectToGoogle']);
    Route::get('/google/token-status', [GoogleWorkspaceController::class, 'tokenStatus']);
    Route::get('/google/sync-docs', [GoogleWorkspaceController::class, 'syncDocs']);
    Route::get('/google/sync-sheets', [GoogleWorkspaceController::class, 'syncSheets']);
    Route::get('/google/sync-slides', [GoogleWorkspaceController::class, 'syncSlides']);
    Route::get('/google/sync-forms', [GoogleWorkspaceController::class, 'syncForms']);
    Route::get('/google/sync-drives', [GoogleWorkspaceController::class, 'syncGoogleDriveFiles']);
    Route::get('/google/settings', [GoogleSettingsController::class, 'show']);
    Route::post('/google/settings', [GoogleSettingsController::class, 'update']);
    Route::delete('/google/{type}/{googleId}', [GoogleWorkspaceController::class, 'destroy']);
    Route::post('/google/create-file', [GoogleWorkspaceController::class, 'createGoogleFile']);

    Route::post(
        'generate-translations',
        [\Modules\GoogleWorkspace\Http\Controllers\Api\GenerateTranslationController::class, 'generateTranslations']
    )->name('api.generate-translations');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post(
        'modules/googleworkspace/activation',
        \Modules\GoogleWorkspace\Http\Controllers\Api\ModuleActivationController::class
    )->name('api.saas.activate-module');
});
