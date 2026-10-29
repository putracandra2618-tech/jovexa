<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /**
     * Normalise optional inputs that browsers submit as empty strings.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('phone') === '') {
            $this->merge(['phone' => null]);
        }

        if ($this->route('user') && $this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
            'phone' => ['nullable', 'string', 'min:8', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'role' => [
                'required',
                Rule::in([
                    UserRole::Customer->value,
                    UserRole::Worker->value,
                    UserRole::Admin->value,
                ]),
            ],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(8),
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
     * Build the attributes persisted to the user record.
     *
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        $role = $this->role();

        $attributes = [
            'name' => $this->validated('name'),
            'email' => $this->validated('email'),
            'phone' => $this->validated('phone'),
            'role_id' => Role::ensureIdFor($role->value, $role->label()),
        ];

        if (filled($this->input('password'))) {
            $attributes['password'] = $this->input('password');
        }

        return $attributes;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda + atau -.',
            'phone.min' => 'Nomor telepon minimal 8 karakter.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
