<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCabinetSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabinet_id' => 'required|exists:cabinets,id',
            'name' => 'required|string|max:255',
            'pic_user_id' => 'required|exists:users,id',
        ];
    }
}
