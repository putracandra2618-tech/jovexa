<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true;
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
                new Enum(OrderStatus::class),
                function (string $attribute, mixed $value, callable $fail): void {
                    $order = $this->order();
                    $target = $value instanceof OrderStatus ? $value : OrderStatus::tryFrom((string) $value);

                    if ($order === null || $target === null) {
                        $fail('Status pesanan tidak dikenal.');

                        return;
                    }

                    if (! $order->status->canTransitionTo($target)) {
                        $fail('Transisi status ini tidak diperbolehkan.');

                        return;
                    }

                    if ($target === OrderStatus::InProgress && in_array($order->status, [OrderStatus::Queued, OrderStatus::WaitingCustomer], strict: true)) {
                        $fail('Pesanan ini harus diteruskan lewat aksi klaim atau balasan customer.');

                        return;
                    }

                    if (! $target->isSettableByAdmin()) {
                        $fail($this->explainReservedStatus($target));
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:1000'],
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
            'status.enum' => 'Status yang dipilih tidak dikenal.',
        ];
    }

    /**
     * The status the admin/worker wants to move the order to.
     */
    public function targetStatus(): OrderStatus
    {
        return OrderStatus::from($this->validated('status'));
    }

    /**
     * Explain why a reserved status cannot be set by hand.
     */
    private function explainReservedStatus(OrderStatus $target): string
    {
        if (in_array($target, OrderStatus::GATEWAY_ONLY, strict: true)) {
            return 'Status pembayaran hanya bisa diubah oleh webhook Tripay.';
        }

        if (in_array($target, OrderStatus::ACTION_ONLY, strict: true)) {
            return 'Pengiriman harus lewat tombol kirim dan wajib menyertakan file hasil.';
        }

        return 'Status ini tidak bisa diubah dari sini.';
    }

    /**
     * Resolve the order from the route, if present.
     */
    private function order(): ?Order
    {
        $order = $this->route('order');

        return $order instanceof Order ? $order : null;
    }
}
