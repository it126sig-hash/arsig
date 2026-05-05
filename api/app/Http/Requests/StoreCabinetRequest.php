<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCabinetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'name' => 'required|string|max:255',
            'points' => 'required|array',
            'needs_coordinate_review' => 'boolean',
        ];
    }
}
