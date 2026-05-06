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
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'cabinet' => new CabinetResource($this->whenLoaded('cabinet')),
            'pic_users' => $this->whenLoaded('picUsers', function () {
                return $this->picUsers->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                ]);
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(fn ($t) => [
                    'id' => $t->id,
                    'nama' => $t->nama,
                ]);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
