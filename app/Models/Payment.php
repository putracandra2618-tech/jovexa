<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'transaction_id', 'tripay_reference', 'payment_method', 'payment_channel', 'amount', 'amount_received', 'total_fee', 'payment_status_id', 'checkout_url', 'expires_at', 'paid_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'amount_received' => 'integer',
            'total_fee' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * The lookup row backing the payment status.
     *
     * @return BelongsTo<PaymentStatusLookup, $this>
     */
    public function statusRecord(): BelongsTo
    {
        return $this->belongsTo(PaymentStatusLookup::class, 'payment_status_id');
    }

    /**
     * Expose the status as its enum so existing callers keep working.
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ?PaymentStatus => PaymentStatus::tryFrom($this->statusRecord?->code ?? ''))
            ->withoutObjectCaching();
    }

    /**
     * The order the payment settles.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Limit the query to payments in the given status.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeWhereStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('payment_status_id', PaymentStatusLookup::idFor($status->value));
    }

    /**
     * Determine whether the payment settled successfully.
     */
    public function isPaid(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * Determine whether the customer can still pay through this transaction.
     */
    public function isStillOpen(): bool
    {
        return $this->status === PaymentStatus::Pending
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
