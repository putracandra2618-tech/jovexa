<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'min:8', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * A browser submits an untouched optional input as an empty string, which
     * would otherwise fail the length rules instead of being treated as absent.
     * Trimming prevents accidental trailing/leading whitespace errors.
     */
    protected function prepareForValidation(): void
    {
        $phone = trim((string) $this->input('phone', ''));

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => trim((string) $this->input('email', '')),
            'phone' => $phone === '' ? null : $phone,
        ]);
    }

    /**
     * The role assigned to every self registered account.
     */
    public function role(): UserRole
    {
        return UserRole::Customer;
    }

    /**
     * Get the error messages for the failed validation attempt.
     *
     * @throws ValidationException
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid (contoh: nama@email.com).',
            'email.max' => 'Alamat email maksimal 255 karakter.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk.',
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda + atau -.',
            'phone.min' => 'Nomor WhatsApp minimal 8 digit.',
            'phone.max' => 'Nomor WhatsApp maksimal 20 digit.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok dengan password di atas.',
        ];
    }
}
