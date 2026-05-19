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
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    public function processTransaction(Request $request, $uuid)
    {
        $invoice = Invoice::where('uuid', $uuid)->with('items.product')->firstOrFail();
        $config = $this->getConfig();

        $provider = new PayPalClient($config);

        if ($invoice->status != InvoiceStatusEnum::UNPAID)
            return back()->with('error', 'Unable to process checkout. Please try again.');

        $paypalToken = $provider->getAccessToken();
        $purchaseItems = $this->getPurchaseItems($invoice);
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.successTransaction', $uuid),
                "cancel_url" => route('paypal.cancelTransaction', $uuid),
            ],
            "purchase_units" => [
                0 => [
                    "reference_id" => $uuid,
                    "invoice_id" => $invoice->invoice_number,
                    "amount" => [
                        "currency_code" => settings('currency'),
                        "value" => round($invoice->total, 2),
                        "breakdown" => [
                            "item_total" => [
                                "currency_code" => settings('currency'),
                                "value" => round($invoice->taxable_amount, 2),
                            ],
                            "tax_total" => [
                                "currency_code" => settings('currency'),
                                "value" => round($invoice->total_tax, 2),
                            ]
                        ]
                    ],
                    'items' => $purchaseItems
                ]
            ]
        ]);
        if (isset($response['id']) && $response['id'] != null) {
            // redirect to approve href
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }
        }

        Log::error('PAYPAL PAYMENT ERROR', ['response' => $response, 'invoice' => $uuid]);
        return redirect()->to(route('invoices.payment', $uuid))->with('error', 'Something went wrong.');
    }

    public function successTransaction(Request $request, $uuid)
    {
        $config = $this->getConfig();

        $provider = new PayPalClient($config);
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);
        $invoice = Invoice::where('uuid', $uuid)->first();

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {

            InvoicePaidEvent::dispatch($invoice);

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'currency' => settings('currency'),
                'status' => 'paid',
                'payment_method' => 'Paypal',
                'paid_at' => now(),
                'response' => json_encode($response),
            ]);
            return redirect()->to(route('invoices.payment', $uuid))->with('success', 'Invoice paid successfully.');
        } else {
            Log::error('PAYPAL PAYMENT ERROR', ['response' => $response, 'invoice' => $uuid]);
            return redirect()->to(route('invoices.payment', $uuid))->with('error', 'Something went wrong.');
        }
    }

    public function cancelTransaction(Request $request, $uuid)
    {
        return redirect()->to(route('invoices.payment', $uuid))->with('error', 'Payment Cancelled.');
    }

    public function getPurchaseItems($invoice)
    {
        $arr = [];
        foreach ($invoice->items as $item)
        {
            $arr[] = [
                "name" => $item->product->name,
                "description" => $item->product->description,
                "sku" => $item->product->sku,
                "unit_amount" => [
                    "currency_code" => settings('currency'),
                    "value" => round($item->taxable_amount, 2)
                ],
                "tax" => [
                    "currency_code" => settings('currency'),
                    "value" => round($item->tax_total, 2)
                ],
                "quantity" => $item->quantity
            ];
        }

        return $arr;
    }

    private function getConfig()
    {
        return [
            'mode'    => settings('paypal_mode'),
            'sandbox' => [
                'client_id'         => settings('paypal_api_key'),
                'client_secret'     => settings('paypal_secret'),
                'app_id'            => 'APP-80W284485P519543T',
            ],
            'live' => [
                'client_id'         => settings('paypal_api_key'),
                'client_secret'     => settings('paypal_secret'),
                'app_id'            => settings('paypal_app_id'),
            ],

            'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'), // Can only be 'Sale', 'Authorization' or 'Order'
            'currency'       => settings('currency'),
            'notify_url'     => env('PAYPAL_NOTIFY_URL', ''), // Change this accordingly for your application.
            'locale'         => env('PAYPAL_LOCALE', 'en_US'), // force gateway language  i.e. it_IT, es_ES, en_US ... (for express checkout only)
            'validate_ssl'   => env('PAYPAL_VALIDATE_SSL', true), // Validate SSL when creating api client.
        ];
    }
}
