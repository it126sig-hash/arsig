<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'points' => 'required|array',
            'needs_coordinate_review' => 'boolean',
        ];
    }
}
