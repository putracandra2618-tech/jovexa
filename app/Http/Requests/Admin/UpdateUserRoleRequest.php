<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::in([
                    UserRole::Customer->value,
                    UserRole::Worker->value,
                    UserRole::Admin->value,
                ]),
            ],
        ];
    }

    /**
     * Get the assigned role from the validated payload.
     */
    public function role(): UserRole
    {
        return UserRole::from($this->validated('role'));
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Peran wajib dipilih.',
            'role.enum' => 'Peran yang dipilih tidak valid.',
        ];
    }
}
