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
            'pic_user_id' => 'sometimes|required|exists:users,id',
        ];
    }
}
