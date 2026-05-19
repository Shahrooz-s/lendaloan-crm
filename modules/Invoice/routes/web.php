<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Http\Controllers\InvoicePaymentController;
use Modules\Invoice\Http\Controllers\PaypalController;
use Modules\Invoice\Http\Controllers\StripePaymentController;

Route::prefix('invoices')->group(function() {
    Route::get('{uuid}/pay', [InvoicePaymentController::class, 'paymentInitiate'])->name('invoices.payment');
    Route::get('{uuid}/download', [InvoicePaymentController::class, 'downloadPDF'])->name('invoices.download');
});

Route::prefix('invoices/{uuid}/paypal')->as('paypal.')->group(function() {
    Route::post('process-transaction', [PayPalController::class, 'processTransaction'])->name('processTransaction');
    Route::get('success-transaction', [PayPalController::class, 'successTransaction'])->name('successTransaction');
    Route::get('cancel-transaction', [PayPalController::class, 'cancelTransaction'])->name('cancelTransaction');
});

Route::prefix('invoices/{uuid}/stripe')->as('stripe.')->group(function() {
    Route::post('process-transaction', [StripePaymentController::class, 'processTransaction'])->name('processTransaction');
});
