<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCabinetSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabinet_id' => 'sometimes|required|exists:cabinets,id',
            'name' => 'sometimes|required|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:aktif,nonaktif,rusak',
            'pic_user_ids' => 'nullable|array',
            'pic_user_ids.*' => 'exists:users,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }
}
