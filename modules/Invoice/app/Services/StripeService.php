<?php

namespace Modules\Invoice\Services;

use Modules\Invoice\Models\Invoice;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(settings('stripe_secret'));
    }

    public function createPaymentSession(Invoice $invoice)
    {
        try {
            return \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => settings('currency') ?? 'USD',
                        'unit_amount' => (int) ($invoice->total * 100),
                        'product_data' => [
                            'name' => "Invoice #{$invoice->invoice_number}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.successTransaction', $invoice->uuid),
                'cancel_url' => route('stripe.cancelTransaction', $invoice->uuid),
                'metadata' => [
                    'order_id' => $invoice->id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe session creation error: ' . $e->getMessage());
            throw $e;
        }
    }
}
