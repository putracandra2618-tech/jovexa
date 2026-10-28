<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServiceStatus;
use App\Models\Lookup\ServiceStatus as ServiceStatusLookup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true;
    }

    /**
     * Fill in the slug before validation so the unique rule checks the value
     * that will actually be persisted, not the empty input.
     */
    protected function prepareForValidation(): void
    {
        if (filled($this->input('slug'))) {
            return;
        }

        $slug = Str::slug((string) $this->input('name'));

        // Names written entirely in a non-Latin script produce an empty slug.
        // A random suffix keeps the service reachable instead of failing at the
        // database's unique index.
        $this->merge([
            'slug' => $slug !== '' ? $slug : 'jasa-'.Str::lower(Str::random(8)),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $serviceId = $this->route('service')?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('services', 'slug')->ignore($serviceId),
            ],
            'description' => ['required', 'string', 'min:20'],
            'price' => ['required', 'integer', 'min:1000'],
            'estimated_duration' => ['required', 'integer', 'min:1', 'max:365'],
            'requirements' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ServiceStatus::class)],
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
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'price.min' => 'Harga minimal Rp1.000.',
            'description.min' => 'Deskripsi minimal 20 karakter agar customer memahami jasanya.',
        ];
    }

    /**
     * Build the attributes to persist for the service.
     *
     * @return array<string, mixed>
     */
    public function serviceAttributes(): array
    {
        $attributes = $this->safe()->except(['slug', 'status']);

        $attributes['slug'] = $this->validated('slug');
        $attributes['service_status_id'] = ServiceStatusLookup::idFor(
            ServiceStatus::from((string) $this->validated('status'))->value,
        );

        return $attributes;
    }
}
