<?php

namespace Modules\Invoice\DTO;

use Modules\Invoice\Enums\InvoiceStatusEnum;
use Modules\Invoice\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceDto
{

    public function __construct(
        public string $invoiceNumber,
        public string $invoiceDate,
        public string $dueDate,
        public string $status,
        public float $total,
        public float $taxTotal,
        public string $contactId,
        public array $invoiceItems,
        public ?string $dealId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        if (!isset($data['invoice_number'])) {
            $maxInvoice = Invoice::max(DB::raw('CAST(invoice_number AS UNSIGNED)'));
            $data['invoice_number'] = $maxInvoice ? sprintf('%08d',  $maxInvoice+ 1) : sprintf('%08d', 1);
        }

        $invoiceItems = [];
        foreach ($data['invoice_items'] as $key => $item) {
            $invoiceItems[$key] = InvoiceItemDto::fromArray($item);
        }

        return new self(
            $data['invoice_number'],
            $data['invoice_date'] ?? date('Y-m-d'),
            $data['due_date'] ?? date('Y-m-d'),
            $data['status'] ?? InvoiceStatusEnum::UNPAID->value,
            $data['total'],
            $data['tax_total'] ?? 0,
            $data['contact_id'] ?? null,
            $invoiceItems ,
            $data['deal_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'taxable_amount' => $this->total - $this->taxTotal,
            'total' => $this->total,
            'tax_total' => $this->taxTotal,
            'date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'status' => $this->status,
            'contact_id' => $this->contactId,
            'deal_id' => $this->dealId,
        ];
    }
}