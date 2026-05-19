<?php

namespace Modules\GoogleWorkspace\Http\Resources;

use Modules\Core\Resource\JsonResource;
use Illuminate\Http\Request;


class GoogleSlidesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'drive_id' => $this->drive_id,
            'is_public' => $this->is_public
        ];
    }
}
