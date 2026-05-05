<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCabinetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'sometimes|required|exists:rooms,id',
            'name' => 'sometimes|required|string|max:255',
            'points' => 'sometimes|required|array',
            'needs_coordinate_review' => 'boolean',
        ];
    }
}
