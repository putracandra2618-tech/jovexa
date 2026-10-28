<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderFileKind as OrderFileKindLookup;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'worker_id', 'service_id', 'order_number', 'description', 'order_status_id', 'total_price', 'worker_claimed_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_price' => 'integer',
            'worker_claimed_at' => 'datetime',
        ];
    }

    /**
     * Orders are addressed by their order number in every URL.
     */
    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * The lookup row backing the order status.
     *
     * @return BelongsTo<OrderStatusLookup, $this>
     */
    public function statusRecord(): BelongsTo
    {
        return $this->belongsTo(OrderStatusLookup::class, 'order_status_id');
    }

    /**
     * Expose the status as its enum so existing callers keep working.
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ?OrderStatus => OrderStatus::tryFrom($this->statusRecord?->code ?? ''))
            ->withoutObjectCaching();
    }

    /**
     * The customer who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The worker currently handling the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * The service that was ordered.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Every file attached to the order.
     *
     * @return HasMany<OrderFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    /**
     * The files the customer uploaded as requirements.
     *
     * @return HasMany<OrderFile, $this>
     */
    public function requirements(): HasMany
    {
        return $this->files()->where(
            'order_file_kind_id',
            OrderFileKindLookup::idFor(OrderFile::KIND_REQUIREMENT),
        );
    }

    /**
     * The deliverables produced by the admin/worker.
     *
     * @return HasMany<OrderFile, $this>
     */
    public function results(): HasMany
    {
        return $this->files()->where(
            'order_file_kind_id',
            OrderFileKindLookup::idFor(OrderFile::KIND_RESULT),
        );
    }

    /**
     * The latest payment record for the order.
     *
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Every payment attempt made for the order.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The audit trail of status changes.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Limit the query to orders matching the given free text term.
     *
     * An order is addressable by its own number or by anything that identifies
     * the customer behind it.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $pattern = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            $query->where('order_number', 'like', $pattern)
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern));
        });
    }

    /**
     * Limit the query to orders in the given statuses.
     *
     * @param  Builder<Order>  $query
     * @param  array<int, OrderStatus>  $statuses
     * @return Builder<Order>
     */
    public function scopeWithStatus(Builder $query, array $statuses): Builder
    {
        return $query->whereIn('order_status_id', array_map(
            fn (OrderStatus $status): int => OrderStatusLookup::idFor($status->value),
            $statuses,
        ));
    }

    /**
     * Limit the query to orders in the given status.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeWhereStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('order_status_id', OrderStatusLookup::idFor($status->value));
    }

    /**
     * Limit the query to orders that are still being worked on.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->withStatus([
            OrderStatus::Paid,
            OrderStatus::WaitingVerification,
            OrderStatus::Queued,
            OrderStatus::WaitingCustomer,
            OrderStatus::InProgress,
            OrderStatus::Revision,
            OrderStatus::Completed,
        ]);
    }

    /**
     * Limit the query to orders waiting for a worker to claim them.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeAvailableForWorkers(Builder $query): Builder
    {
        return $query->whereStatus(OrderStatus::Queued)->whereNull('worker_id');
    }

    /**
     * Limit the query to orders assigned to the given worker.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeAssignedTo(Builder $query, User $worker): Builder
    {
        return $query->where('worker_id', $worker->getKey());
    }

    /**
     * Limit the query to the worker's unfinished workload.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeActiveForWorker(Builder $query, User $worker): Builder
    {
        return $query
            ->assignedTo($worker)
            ->whereNotIn('order_status_id', [
                OrderStatusLookup::idFor(OrderStatus::Delivered->value),
                OrderStatusLookup::idFor(OrderStatus::Cancelled->value),
            ]);
    }

    /**
     * Determine whether the order is assigned to the given worker.
     */
    public function isAssignedTo(User $worker): bool
    {
        return $this->worker_id === $worker->getKey();
    }

    /**
     * Determine whether the order has been paid for.
     *
     * An expired order is explicitly not paid, since the customer still has to
     * settle it before any work may start.
     */
    public function isPaid(): bool
    {
        return ! in_array($this->status, [OrderStatus::PendingPayment, OrderStatus::Expired, OrderStatus::Cancelled], strict: true);
    }

    /**
     * Determine whether the customer may still start a payment for this order.
     */
    public function isPayable(): bool
    {
        return $this->status->isPayable();
    }

    /**
     * Determine whether the order results may be read by the customer.
     */
    public function canDownloadResults(): bool
    {
        return $this->status->hasDeliverables();
    }
}
