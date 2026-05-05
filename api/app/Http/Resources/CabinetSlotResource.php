<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabinetSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cabinet_id' => $this->cabinet_id,
            'name' => $this->name,
            'pic_user_id' => $this->pic_user_id,
            'cabinet' => new CabinetResource($this->whenLoaded('cabinet')),
            'pic_user' => $this->whenLoaded('picUser', function () {
                return [
                    'id' => $this->picUser->id,
                    'name' => $this->picUser->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
