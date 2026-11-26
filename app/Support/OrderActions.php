<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\RevisionService;

class OrderActions
{
    public function __construct(private readonly RevisionService $revisions) {}

    /**
     * Build the single, ordered list of actions available for this screen.
     *
     * @return list<array<string, mixed>>
     */
    public function for(Order $order, User $user): array
    {
        if ($user->isCustomer()) {
            return $this->customerActions($order);
        }

        if ($user->isWorker()) {
            return $this->workerActions($order);
        }

        return $this->adminActions($order);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adminActions(Order $order): array
    {
        $actions = [];
        $status = $order->status;
        $next = $status->next();

        if ($next !== null
            && $next->isSettableByAdmin()
            && ! in_array($status, [OrderStatus::Paid, OrderStatus::Queued, OrderStatus::WaitingCustomer, OrderStatus::Revision], true)) {
            $actions[] = $this->statusAction(
                $order,
                'admin.orders.status',
                'next',
                $next,
                $status === OrderStatus::WaitingVerification ? 'Verifikasi & Minta Bayar' : 'Lanjut ke '.$next->label(),
                'primary',
                'Catatan (opsional)',
                false,
                'Contoh: Data sudah dicek, silakan lanjut pembayaran.',
            );
        }

        if ($status === OrderStatus::WaitingCustomer && ! $order->worker) {
            $actions[] = $this->statusAction(
                $order,
                'admin.orders.status',
                'information-received',
                OrderStatus::WaitingVerification,
                'Informasi Sudah Diterima',
                'light',
            );
        }

        if ($status === OrderStatus::WaitingVerification) {
            $actions[] = $this->informationAction($order, 'admin.orders.information', 'request-information');
        }

        if ($status === OrderStatus::Revision) {
            $actions[] = $this->statusAction(
                $order,
                'admin.orders.status',
                'approve-revision',
                OrderStatus::Completed,
                'Setujui Revisi & Selesai',
                'primary',
                'Catatan Persetujuan (opsional)',
                false,
                'Contoh: Revisi sudah diperbaiki sesuai catatan.',
            );
        }

        if ($status->canTransitionTo(OrderStatus::Revision) && $this->revisions->canRequest($order)) {
            $actions[] = $this->revisionAction($order, 'admin.orders.status');
        }

        array_push($actions, ...$this->deliveryActions($order, 'admin'));

        if (! $status->isFinal()) {
            $actions[] = $this->cancelAction($order, 'admin.orders.cancel');
        }

        return $actions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function workerActions(Order $order): array
    {
        $actions = [];
        $status = $order->status;

        if (in_array($status, [OrderStatus::InProgress, OrderStatus::Revision], true)) {
            $actions[] = $this->statusAction(
                $order,
                'worker.orders.status',
                'complete',
                OrderStatus::Completed,
                'Tandai Selesai',
                'primary',
                'Catatan (opsional)',
                false,
                'Contoh: Pekerjaan sudah selesai dan siap dikirim.',
            );
        }

        if ($status === OrderStatus::InProgress && $status->canTransitionTo(OrderStatus::WaitingCustomer)) {
            $actions[] = $this->informationAction($order, 'worker.orders.information', 'request-information');
        }

        array_push($actions, ...$this->deliveryActions($order, 'worker'));

        return $actions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customerActions(Order $order): array
    {
        $actions = [];
        $status = $order->status;

        if ($status->isPayable()) {
            $actions[] = [
                'key' => 'pay',
                'type' => 'link',
                'label' => 'Bayar Sekarang',
                'icon' => 'bi-wallet2',
                'variant' => 'primary',
                'route' => route('customer.orders.pay', $order),
            ];
        }

        if ($status === OrderStatus::WaitingCustomer) {
            $actions[] = [
                'key' => 'requirements',
                'type' => 'form',
                'label' => 'Kirim File',
                'icon' => 'bi-cloud-arrow-up',
                'variant' => 'primary',
                'route' => route('customer.orders.requirements', $order),
                'method' => 'POST',
                'file' => [
                    'name' => 'requirements[]',
                    'label' => 'Unggah File Pendukung',
                    'hint' => 'PDF, DOC, DOCX, TXT, ZIP, PNG, atau JPG. Bisa pilih beberapa file sekaligus.',
                    'attributes' => 'multiple accept=".pdf,.doc,.docx,.txt,.zip,.png,.jpg,.jpeg"',
                ],
            ];
        }

        if ($this->revisions->canCustomerRequest($order)) {
            $actions[] = $this->revisionAction($order, 'customer.orders.revision');
        }

        if ($order->canRequestRefund()) {
            $refundAction = $this->refundAction($order);

            if ($refundAction !== null) {
                $actions[] = $refundAction;
            }
        } elseif ($order->canBeCancelledByCustomer()) {
            $actions[] = $this->cancelAction($order, 'customer.orders.destroy', true);
        }

        return $actions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function deliveryActions(Order $order, string $prefix): array
    {
        $isRevision = $order->status === OrderStatus::Revision;
        $isCompleted = $order->status === OrderStatus::Completed;

        if (! $isRevision && ! $isCompleted) {
            return [];
        }

        $actions = [];

        if ($isCompleted || $prefix === 'worker') {
            $actions[] = [
                'key' => 'upload-result',
                'type' => 'link',
                'label' => $isRevision ? 'Unggah Hasil Revisi' : 'Unggah Hasil',
                'icon' => 'bi-cloud-arrow-up',
                'variant' => 'primary',
                'route' => $prefix === 'admin'
                    ? route('admin.orders.deliver', $order)
                    : route('worker.orders.show', $order).'#unggah-hasil',
            ];
        }

        if ($isCompleted && $order->deliverables()->exists()) {
            $actions[] = [
                'key' => 'send',
                'type' => 'form',
                'label' => 'Kirim ke Customer',
                'icon' => 'bi-send',
                'variant' => 'primary',
                'route' => $prefix === 'admin'
                    ? route('admin.orders.send', $order)
                    : route('worker.orders.send', $order),
                'method' => 'POST',
                'confirm' => 'Kirim hasil pekerjaan ke customer?',
            ];
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function statusAction(
        Order $order,
        string $routeName,
        string $key,
        OrderStatus $target,
        string $label,
        string $variant,
        ?string $noteLabel = null,
        bool $noteRequired = false,
        ?string $placeholder = null,
    ): array {
        $action = [
            'key' => $key,
            'type' => 'form',
            'label' => $label,
            'variant' => $variant,
            'route' => route($routeName, $order),
            'method' => 'PATCH',
            'hidden' => [['name' => 'status', 'value' => $target->value]],
            'confirm' => "Yakin {$label}?",
        ];

        if ($noteLabel !== null) {
            $action['note'] = [
                'name' => 'note',
                'label' => $noteLabel,
                'required' => $noteRequired,
                'placeholder' => $placeholder,
            ];
        }

        return $action;
    }

    /**
     * @return array<string, mixed>
     */
    private function revisionAction(Order $order, string $routeName): array
    {
        return [
            'key' => 'revision',
            'type' => 'form',
            'label' => 'Minta Revisi',
            'icon' => 'bi-arrow-repeat',
            'variant' => 'warning',
            'route' => route($routeName, $order),
            'method' => $routeName === 'customer.orders.revision' ? 'POST' : 'PATCH',
            'hidden' => $routeName === 'customer.orders.revision'
                ? []
                : [['name' => 'status', 'value' => OrderStatus::Revision->value]],
            'confirm' => 'Ajukan permintaan revisi untuk pesanan ini?',
            'note' => [
                'name' => $routeName === 'customer.orders.revision' ? 'revision_note' : 'note',
                'label' => 'Catatan Revisi',
                'required' => true,
                'placeholder' => 'Jelaskan bagian yang perlu diperbaiki...',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function informationAction(Order $order, string $routeName, string $key): array
    {
        return [
            'key' => $key,
            'type' => 'form',
            'label' => 'Minta Informasi',
            'icon' => 'bi-chat-left-dots',
            'variant' => 'light',
            'route' => route($routeName, $order),
            'method' => 'POST',
            'confirm' => 'Minta informasi tambahan ke customer?',
            'note' => [
                'name' => 'note',
                'label' => 'Minta Informasi ke Customer',
                'required' => true,
                'placeholder' => 'Contoh: Mohon kirim data lengkap bab 1.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function refundAction(Order $order): ?array
    {
        $whatsapp = config('jovexa.contact.whatsapp');

        if (blank($whatsapp)) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', (string) $whatsapp);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        $message = sprintf(
            'Halo Admin Jovexa, saya ingin mengajukan refund untuk pesanan %s (%s). Mohon ditindaklanjuti.',
            $order->order_number,
            $order->service->name,
        );

        return [
            'key' => 'refund',
            'type' => 'link',
            'label' => 'Request Refund',
            'icon' => 'bi-whatsapp',
            'variant' => 'warning',
            'route' => 'https://wa.me/'.$digits.'?text='.rawurlencode($message),
            'target' => '_blank',
            'rel' => 'noopener',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cancelAction(Order $order, string $routeName, bool $methodDelete = false): array
    {
        return [
            'key' => 'cancel',
            'type' => 'form',
            'label' => 'Batalkan Pesanan',
            'variant' => 'danger',
            'route' => route($routeName, $order),
            'method' => $methodDelete ? 'DELETE' : 'POST',
            'confirm' => 'Batalkan pesanan '.$order->order_number.'?',
        ];
    }
}
