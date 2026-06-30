<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'role' => ['required', Rule::in(['root', 'admin', 'user'])],
            'level' => ['required', Rule::in(['staff', 'supervisor', 'manager', 'direksi'])],
        ];
    }
}
