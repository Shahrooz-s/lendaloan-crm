<?php

namespace Modules\Sms\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Resource\JsonResource;

class SmsTemplateResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'body' => $this->body,
        ], $request);
    }
}
