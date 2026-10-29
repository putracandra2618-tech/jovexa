<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'old_password' => ['nullable', 'string', 'min:8'],
        ];
    }

    /**
     * Get custom attributes for validator entities.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => 'Password',
            'old_password' => 'Password Lama',
        ];
    }

    /**
     * Get the validation messages that apply to the form request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'old_password.required' => 'Password lama wajib diisi saat mengganti password.',
        ];
    }
}