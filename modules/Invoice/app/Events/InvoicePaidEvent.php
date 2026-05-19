<?php

namespace Modules\Invoice\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Invoice\Models\Invoice;

class InvoicePaidEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Invoice $invoice)
    {}
}
