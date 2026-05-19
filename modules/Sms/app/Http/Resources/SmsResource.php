<?php

namespace Modules\Sms\Http\Resources;

use Modules\Core\Resource\JsonResource;
use Illuminate\Http\Request;

class SmsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'crm_function' => $this->crm_function,
            'recipients' => $this->recipients,
            'message' => $this->message,
            'status' => $this->status,
            'provider_message_id' => $this->provider_message_id,
        ];
    }
}
