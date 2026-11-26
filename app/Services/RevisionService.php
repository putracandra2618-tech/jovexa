<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevisionService
{
    public const MAX_PER_ORDER = 3;

    public function __construct(
        private readonly OrderWorkflow $workflow,
        private readonly UserNotificationService $notifications,
    ) {}

    /**
     * Record a revision request, enforce the per-order quota and notify the team.
     */
    public function request(Order $order, User $requester, string $note, string $errorField = 'revision_note'): Order
    {
        $note = trim($note);

        if ($note === '') {
            throw ValidationException::withMessages([
                $errorField => 'Catatan revisi wajib diisi.',
            ]);
        }

        if (mb_strlen($note) > 1000) {
            throw ValidationException::withMessages([
                $errorField => 'Catatan revisi maksimal 1000 karakter.',
            ]);
        }

        return DB::transaction(function () use ($order, $requester, $note, $errorField): Order {
            $used = $order->revisionRequests()->count();

            if ($used >= self::MAX_PER_ORDER) {
                throw ValidationException::withMessages([
                    $errorField => 'Kuota revisi pesanan ini sudah habis (maksimal 3 kali).',
                ]);
            }

            $updated = $this->workflow->transitionTo(
                $order,
                OrderStatus::Revision,
                $requester,
                "Revisi diminta oleh {$requester->name}: {$note}",
            );

            $updated->revisionRequests()->create([
                'requested_by' => $requester->getKey(),
                'revision_number' => $used + 1,
                'note' => $note,
            ]);

            $this->notifications->revisionRequested($updated, $requester, $note);

            return $updated;
        });
    }

    /**
     * Whether this order may receive another revision request.
     */
    public function canRequest(Order $order): bool
    {
        return $order->status?->canTransitionTo(OrderStatus::Revision) === true
            && $order->revisionRequests()->count() < self::MAX_PER_ORDER;
    }

    /**
     * How many revision requests have already been recorded.
     */
    public function used(Order $order): int
    {
        return $order->revisionRequests()->count();
    }
}
