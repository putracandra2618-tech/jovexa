<?php

namespace App\Http\Requests\Worker;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isWorker() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    OrderStatus::Completed->value,
                    OrderStatus::WaitingCustomer->value,
                    OrderStatus::Revision->value,
                ]),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $order = $this->route('order');
                $target = OrderStatus::tryFrom((string) $this->input('status'));

                if (! $order instanceof Order || $target === null) {
                    $validator->errors()->add('status', 'Status pesanan tidak dikenal.');

                    return;
                }

                if (! $order->status->canTransitionTo($target)) {
                    $validator->errors()->add('status', 'Transisi status ini tidak diperbolehkan.');
                }
            },
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
            'status.required' => 'Pilih status baru untuk pesanan.',
            'status.in' => 'Status yang dipilih tidak bisa diatur oleh worker.',
        ];
    }

    /**
     * The status the worker wants to move the order to.
     */
    public function targetStatus(): OrderStatus
    {
        return OrderStatus::from($this->validated('status'));
    }
}
