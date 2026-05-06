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
            'keterangan' => 'nullable|string|max:1000',
            'door_count' => 'nullable|string|max:20|regex:/^\d+\s*\*\s*\d+$/',
            'points' => 'required|array',
            'needs_coordinate_review' => 'boolean',
        ];
    }
}
