<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isCustomer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();
        $userId = $user?->getKey();

        $emailChanged = $user !== null
            && strcasecmp((string) $this->input('email'), (string) $user->email) !== 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'old_password' => [
                'nullable',
                'string',
                'min:8',
                // Changing the email is just as sensitive as changing the
                // password: both require proof of the current password.
                Rule::requiredIf($emailChanged || filled($this->input('password'))),
            ],
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
            'name' => 'Username',
            'email' => 'Email',
            'password' => 'Password Baru',
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
            'name.required' => 'Username wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'old_password.required' => 'Password lama wajib diisi saat mengganti email atau password.',
        ];
    }
}
