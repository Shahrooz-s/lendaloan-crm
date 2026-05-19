<?php

use Illuminate\Support\Facades\Route;
use Modules\Sms\Http\Controllers\Api\ActivityController;
use Modules\Sms\Http\Controllers\Api\GenerateTranslationController;
use Modules\Sms\Http\Controllers\Api\ModuleActivationController;
use Modules\Sms\Http\Controllers\Api\SmsController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('send-sms', [SmsController::class, 'sendSms']);
    Route::get('activity-types', [ActivityController::class, 'activityTypes']);
    Route::get('activity-by-type/{activityTypeId}', [ActivityController::class, 'getAllByActivityTypeId']);

    Route::post('generate-translations', [GenerateTranslationController::class, 'generateTranslations'])->name('api.generate-translations');
    Route::post('modules/sms/activation', ModuleActivationController::class)->name('api.sms.activate-module');
    Route::get('sms-templates', [SmsController::class, 'searchTemplate'])->name('api.templates.search');
});

Route::any('/twilio/webhook/status-changed', [SmsController::class, 'statusChanged'])->middleware(['is-twilio-request'])->name('api.twilio.status-changed');
