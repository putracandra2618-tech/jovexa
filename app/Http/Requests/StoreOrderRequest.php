<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\OrderFile;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Customer) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'requirements' => ['nullable', 'array', 'max:5'],
            'requirements.*' => ['file', 'max:10240', 'mimes:'.implode(',', OrderFile::ALLOWED_EXTENSIONS)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Tuliskan kebutuhan yang ingin kamu kerjakan.',
            'description.min' => 'Detail kebutuhan minimal 20 karakter agar kami paham apa yang kamu butuhkan.',
            'requirements.max' => 'Maksimal 5 file pendukung per pesanan.',
            'requirements.*.file' => 'Lampiran harus berupa file.',
            'requirements.*.max' => 'Setiap file maksimal 10 MB.',
            'requirements.*.mimes' => 'Format lampiran tidak didukung. Gunakan dokumen, gambar, atau arsip.',
        ];
    }
}
