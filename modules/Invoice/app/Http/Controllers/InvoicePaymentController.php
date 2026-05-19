<?php

namespace Modules\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\Models\Country;
use Modules\Core\Models\CustomField;
use Modules\Invoice\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function paymentInitiate($id): View
    {
        if (is_numeric($id))
            $field = 'id';
        else
            $field = 'uuid';

        $invoice = Invoice::with('customer', 'items.product')->where($field, $id)->firstOrFail();
        $country = Country::where('id', settings('company_country_id'))->first();
        $customFields = CustomField::query()->where('resource_name', \Modules\Invoice\Resources\Invoice::name())->get();
        $extraFields = [];

        foreach ($customFields as $customField) {
            if ($invoice[$customField->field_id])
                $extraFields[] = [
                    'label' => $customField->label,
                    'value' => $invoice[$customField->field_id] ?? null
                ];
        }

        if ($country)
            $country = $country->full_name;
        else
            $country = '';

        return view('invoice::invoice-payment', compact('invoice', 'country', 'extraFields'));
    }

    public function downloadPDF($uuid) {
        $invoice = Invoice::where('uuid', $uuid)->firstOrFail();
        $country = Country::where('id', settings('company_country_id'))->first();
        $customFields = CustomField::query()->where('resource_name', \Modules\Invoice\Resources\Invoice::name())->get();
        $extraFields = [];

        foreach ($customFields as $customField) {
            if ($invoice[$customField->field_id])
                $extraFields[] = [
                    'label' => $customField->label,
                    'value' => $invoice[$customField->field_id] ?? null
                ];
        }

        if ($country)
            $country = $country->full_name;
        else
            $country = '';

        $pdf = Pdf::loadView('invoice::invoice-pdf', compact('invoice', 'country', 'extraFields'));

        return $pdf->download("{$invoice->invoice_number}.pdf");
    }

}
