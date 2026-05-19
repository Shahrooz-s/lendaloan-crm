<?php

use Modules\GoogleWorkspace\Http\Controllers\Api\GoogleWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('googleworkspace')->group(function () {
    Route::get('/docs/{id}/iframe', [GoogleWorkspaceController::class, 'iframe']);
});
