<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'floor_id' => 'sometimes|required|exists:floors,id',
            'name' => 'sometimes|required|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
            'points' => 'sometimes|required|array',
            'needs_coordinate_review' => 'boolean',
        ];
    }
}
