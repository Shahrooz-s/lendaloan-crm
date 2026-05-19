<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Http\Controllers\Api\GenerateTranslationController;
use Modules\Invoice\Http\Controllers\Api\ModuleActivationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('generate-translations', [GenerateTranslationController::class, 'generateTranslations'])->name('api.generate-translations');
    Route::post('modules/invoice/activation', ModuleActivationController::class)->name('invoice.api.activate-module');
});
