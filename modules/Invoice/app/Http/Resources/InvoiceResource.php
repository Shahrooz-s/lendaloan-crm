<?php

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Modules\Contacts\Http\Resources\ContactResource;
use Modules\Core\Resource\JsonResource;
use Modules\Deals\Http\Resources\DealResource;
use Modules\Users\Http\Resources\UserResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'invoice_number' => $this->invoice_number,
            'total' => $this->total,
            'taxable_amount' => $this->taxable_amount,
            'total_tax' => $this->total_tax,
            'due_date' => $this->due_date,
            'date' => $this->date,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'deal' => new DealResource($this->whenLoaded('deal')),
        ], $request);
    }
}
