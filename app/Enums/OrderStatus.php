<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case WaitingVerification = 'waiting_verification';
    case Queued = 'queued';
    case WaitingCustomer = 'waiting_customer';
    case InProgress = 'in_progress';
    case Revision = 'revision';
    case Completed = 'completed';
    case Delivered = 'delivered';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * Statuses that may only be reached through the payment gateway callback.
     *
     * Letting the admin/worker set these by hand would defeat the whole point of
     * confirming payments server side, so the status change request rejects them
     * outright.
     */
    public const GATEWAY_ONLY = [self::Paid, self::Expired];

    /**
     * Statuses that may only be reached through a dedicated action.
     *
     * Delivering has to go through the send endpoint, which refuses to run unless
     * at least one result file is attached to the order.
     */
    public const ACTION_ONLY = [self::Delivered];

    /**
     * The human readable label used across the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Sudah Dibayar',
            self::WaitingVerification => 'Menunggu Verifikasi',
            self::Queued => 'Menunggu Worker',
            self::WaitingCustomer => 'Menunggu Info Customer',
            self::InProgress => 'Sedang Dikerjakan',
            self::Revision => 'Perlu Revisi',
            self::Completed => 'Selesai',
            self::Delivered => 'Sudah Dikirim',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * The Bootstrap contextual class used by status badges.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingPayment, self::Paid, self::WaitingVerification, self::Queued, self::InProgress => 'pending',
            self::WaitingCustomer, self::Revision, self::Expired => 'failed',
            self::Completed, self::Delivered => 'success',
            self::Cancelled => 'failed',
        };
    }

    /**
     * The status that follows this one along the happy path of the order flow.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::PendingPayment => self::Paid,
            self::Expired => self::PendingPayment,
            self::Paid => self::WaitingVerification,
            self::WaitingVerification => self::Queued,
            self::Queued => self::InProgress,
            self::WaitingCustomer => self::WaitingVerification,
            self::InProgress => self::Completed,
            self::Revision => self::Completed,
            self::Completed => self::Delivered,
            self::Delivered, self::Cancelled => null,
        };
    }

    /**
     * The happy path of an order as the customer experiences it.
     *
     * Several statuses collapse into the same step, so a customer asking "where is
     * my order?" always sees a short, stable list instead of every internal status.
     *
     * @return list<array{label: string, icon: string, statuses: list<self>}>
     */
    public static function customerSteps(): array
    {
        return [
            ['label' => 'Bayar', 'icon' => 'bi-wallet2', 'statuses' => [self::PendingPayment, self::Expired]],
            ['label' => 'Terkonfirmasi', 'icon' => 'bi-receipt', 'statuses' => [self::Paid]],
            ['label' => 'Diverifikasi', 'icon' => 'bi-shield-check', 'statuses' => [self::WaitingVerification, self::WaitingCustomer]],
            ['label' => 'Dikerjakan', 'icon' => 'bi-hammer', 'statuses' => [self::Queued, self::InProgress, self::Revision]],
            ['label' => 'Selesai', 'icon' => 'bi-stars', 'statuses' => [self::Completed]],
            ['label' => 'Dikirim', 'icon' => 'bi-send-check', 'statuses' => [self::Delivered]],
        ];
    }

    /**
     * Determine which customer facing step this status belongs to.
     *
     * Returns -1 for statuses that sit outside the happy path, such as a
     * cancellation, so callers can render a timeline without a progress bar.
     */
    public function customerStepIndex(): int
    {
        foreach (self::customerSteps() as $index => $step) {
            if (in_array($this, $step['statuses'], strict: true)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * Determine whether the order may move to the given status.
     *
     * Staff may ask the customer a question while the order is being handled,
     * may send work back for revision, and any order that has not been delivered
     * yet may be cancelled.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($target === $this) {
            return false;
        }

        if ($target === self::Cancelled) {
            return ! in_array($this, [self::Delivered, self::Cancelled], strict: true);
        }

        // A pending payment may run out of time. An expired order may be retried,
        // or settle outright when the customer finished paying just in time.
        if ($target === self::Expired) {
            return $this === self::PendingPayment;
        }

        if ($this === self::Expired) {
            return in_array($target, [self::PendingPayment, self::Paid], strict: true);
        }

        if ($target === self::WaitingCustomer) {
            return in_array($this, [self::Paid, self::WaitingVerification, self::Queued, self::InProgress, self::Revision], strict: true);
        }

        // A customer answering a question returns the order to its worker when
        // one is assigned. The unassigned case still follows next().
        if ($this === self::WaitingCustomer && $target === self::InProgress) {
            return true;
        }

        if ($target === self::Revision) {
            return in_array($this, [self::InProgress, self::Completed], strict: true);
        }

        return $this->next() === $target;
    }

    /**
     * Determine whether the order has reached a terminal state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], strict: true);
    }

    /**
     * Determine whether the order still accepts a payment attempt.
     */
    public function isPayable(): bool
    {
        return in_array($this, [self::PendingPayment, self::Expired], strict: true);
    }

    /**
     * Determine whether the order results are readable by the customer.
     */
    public function hasDeliverables(): bool
    {
        return in_array($this, [self::Completed, self::Delivered], strict: true);
    }

    /**
     * Determine whether the status may be set manually by staff.
     */
    public function isSettableByAdmin(): bool
    {
        return ! in_array($this, [...self::GATEWAY_ONLY, ...self::ACTION_ONLY], strict: true);
    }
}
