<?php

namespace Modules\Invoice\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Invoice\DTO\InvoiceDto;

class InvoiceCreate
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(private readonly array $invoice)
    {}

    public function getInvoiceDto(): InvoiceDto
    {
        return InvoiceDto::fromArray($this->invoice);
    }

    public function getInvoiceArray(): array
    {
        return $this->invoice;
    }

    public function shouldSendNotification(): bool
    {
        return $this->invoice['should_send_notification'] ?? false;
    }
}