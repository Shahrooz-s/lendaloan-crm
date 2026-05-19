<?php

namespace Modules\Invoice\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Invoice\Models\Invoice;

class InvoiceCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public array $invoiceData, public Invoice $invoice)
    {}
}