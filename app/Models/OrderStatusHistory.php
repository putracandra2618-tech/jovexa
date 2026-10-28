<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'from_order_status_id', 'to_order_status_id', 'changed_by', 'note'])]
class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory;

    /**
     * The lookup row for the status the order came from.
     *
     * @return BelongsTo<OrderStatusLookup, $this>
     */
    public function fromStatusRecord(): BelongsTo
    {
        return $this->belongsTo(OrderStatusLookup::class, 'from_order_status_id');
    }

    /**
     * The lookup row for the status the order moved to.
     *
     * @return BelongsTo<OrderStatusLookup, $this>
     */
    public function toStatusRecord(): BelongsTo
    {
        return $this->belongsTo(OrderStatusLookup::class, 'to_order_status_id');
    }

    /**
     * Expose the previous status as its enum.
     */
    protected function fromStatus(): Attribute
    {
        return Attribute::get(fn (): ?OrderStatus => OrderStatus::tryFrom($this->fromStatusRecord?->code ?? ''))
            ->withoutObjectCaching();
    }

    /**
     * Expose the target status as its enum.
     */
    protected function toStatus(): Attribute
    {
        return Attribute::get(fn (): ?OrderStatus => OrderStatus::tryFrom($this->toStatusRecord?->code ?? ''))
            ->withoutObjectCaching();
    }

    /**
     * The order the change belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The account that triggered the change, when it was a manual action.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
