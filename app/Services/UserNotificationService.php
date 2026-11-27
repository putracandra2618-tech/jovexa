<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserNotificationService
{
    /**
     * Tell admins and the assigned worker that a revision is waiting.
     */
    public function revisionRequested(Order $order, User $requester, string $note): void
    {
        $staff = User::query()
            ->whereIn('role_id', [
                Role::idFor(UserRole::Admin->value),
                Role::idFor(UserRole::SuperAdmin->value),
            ])
            ->get();

        $this->notify(
            $staff->concat([$order->worker])->filter(),
            $order,
            UserNotification::RevisionRequested,
            'Ada permintaan revisi',
            'Pesanan '.$order->order_number.' perlu revisi: '.Str::limit($note, 120),
            $requester,
        );
    }

    /**
     * Tell the customer that their revision has been accepted.
     */
    public function revisionApproved(Order $order): void
    {
        $this->notify(
            collect([$order->user]),
            $order,
            UserNotification::RevisionApproved,
            'Revisi disetujui',
            'Revisi pesanan '.$order->order_number.' sudah selesai. Hasilnya segera dikirim.',
        );
    }

    /**
     * Tell the customer that the result file has been delivered.
     */
    public function resultDelivered(Order $order): void
    {
        $this->notify(
            collect([$order->user]),
            $order,
            UserNotification::ResultDelivered,
            'Hasil pesanan dikirim',
            'Hasil pesanan '.$order->order_number.' sudah tersedia. Silakan unduh di halaman pesanan.',
        );
    }

    /**
     * Tell admins and the assigned worker that the customer accepted the result.
     */
    public function orderAccepted(Order $order, User $accepter): void
    {
        $staff = User::query()
            ->whereIn('role_id', [
                Role::idFor(UserRole::Admin->value),
                Role::idFor(UserRole::SuperAdmin->value),
            ])
            ->get();

        $this->notify(
            $staff->concat([$order->worker])->filter(),
            $order,
            UserNotification::OrderAccepted,
            'Pesanan diterima customer',
            'Pesanan '.$order->order_number.' diterima oleh '.$accepter->name.' dan dinyatakan selesai.',
            $accepter,
        );
    }

    /**
     * Tell the customer what the staff member needs to continue.
     */
    public function informationRequested(Order $order, User $requester, string $note): void
    {
        $this->notify(
            collect([$order->user]),
            $order,
            UserNotification::InformationRequested,
            'Tim membutuhkan informasi',
            'Pesanan '.$order->order_number.': '.Str::limit($note, 120),
            $requester,
        );
    }

    /**
     * Tell the staff member who requested information that the customer replied.
     */
    public function informationReplied(Order $order, User $recipient, string $note): void
    {
        $this->notify(
            collect([$recipient]),
            $order,
            UserNotification::InformationReplied,
            'Customer membalas informasi',
            'Pesanan '.$order->order_number.': '.Str::limit($note, 120),
            $order->user,
        );
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function notify(
        Collection $recipients,
        Order $order,
        string $type,
        string $title,
        string $body,
        ?User $exclude = null,
    ): void {
        $recipients
            ->unique(fn (User $user): int => $user->getKey())
            ->reject(fn (User $user): bool => $exclude !== null && $user->is($exclude))
            ->each(function (User $user) use ($order, $type, $title, $body): void {
                $user->inAppNotifications()->create([
                    'order_id' => $order->getKey(),
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                ]);
            });
    }
}
