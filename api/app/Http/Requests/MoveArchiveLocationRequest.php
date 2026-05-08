<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveArchiveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_floor_id' => 'required|exists:floors,id',
            'new_room_id' => 'required|exists:rooms,id',
            'new_cabinet_id' => 'required|exists:cabinets,id',
            'new_cabinet_slot_id' => 'nullable|exists:cabinet_slots,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
