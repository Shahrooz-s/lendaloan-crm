<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Events\InvoiceCreated;
use \Modules\Invoice\Events\InvoiceCreate as InvoiceCreateEvent;
use Modules\Invoice\Models\Customer as CustomerModel;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Notifications\Customer\NewInvoiceNotification;

class InvoiceCreate
{
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InvoiceCreateEvent $event): void
    {
        $invoiceDto = $event->getInvoiceDto();

        $invoice = Invoice::query()->create($invoiceDto->toArray());
        foreach ($invoiceDto->invoiceItems as $invoiceItem) {
            $invoice->items()->create($invoiceItem->toArray());
        }

        if ($event->shouldSendNotification()) {
            if (!$invoice->relationLoaded('customer')) {
                $invoice->load('customer');
            }

            $customer = $invoice->customer;

            if (isset($customer->email)) {
                $customerModel = new CustomerModel();
                $customerModel->fill($customer->toArray());
                $customerModel->id = $customer->id;

                $customerModel->notify(new NewInvoiceNotification($invoice, $customer));
            }
        }

        InvoiceCreated::dispatch($event->getInvoiceArray(), $invoice);
    }
}
