<?php

namespace Modules\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Actions\ActionFields;
use Modules\Invoice\Enums\InvoiceStatusEnum;
use Modules\Invoice\Events\InvoicePaidEvent;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\Payment;
use Stripe\Charge;
use Stripe\Stripe;

class StripePaymentController extends Controller
{
    public function processTransaction(Request $request, $uuid)
    {
        $invoice = Invoice::with('items.product')->where('uuid', $uuid)->firstOrFail();

        try {
            if ($invoice->status != InvoiceStatusEnum::UNPAID) {
                return back()->with('error', 'Unable to process checkout. Please try again.');
            }

            Stripe::setApiKey(settings('stripe_secret'));
            $response = Charge::create ([
                "amount" => (int) ($invoice->total * 100),
                "currency" => settings('currency') ?? 'USD',
                "source" => $request->stripeToken,
                "description" => "Payment for invoice #{$invoice->invoice_number}",
            ]);

            InvoicePaidEvent::dispatch($invoice);

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'currency' => settings('currency'),
                'status' => 'paid',
                'payment_method' => 'Stripe',
                'paid_at' => now(),
                'response' => json_encode($response),
            ]);

            return back()->with('success', 'Invoice paid successfully.');
        } catch (\Exception $e) {
            Log::error('Checkout error: ' . $e->getMessage());
            return back()->with('error', 'Unable to process checkout. Please try again.');
        }
    }

}
