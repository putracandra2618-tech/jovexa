<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use App\Models\Lookup\ServiceStatus as ServiceStatusLookup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'price', 'estimated_duration', 'requirements', 'service_status_id'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'estimated_duration' => 'integer',
        ];
    }

    /**
     * Services are addressed by slug in every URL, public and admin alike.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The lookup row backing the status.
     *
     * @return BelongsTo<ServiceStatusLookup, $this>
     */
    public function statusRecord(): BelongsTo
    {
        return $this->belongsTo(ServiceStatusLookup::class, 'service_status_id');
    }

    /**
     * Expose the status as its enum so existing callers keep working.
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ?ServiceStatus => ServiceStatus::tryFrom($this->statusRecord?->code ?? ''))
            ->withoutObjectCaching();
    }

    /**
     * The orders placed against this service.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Limit the query to services in the given status.
     *
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeWhereStatus(Builder $query, ServiceStatus $status): Builder
    {
        return $query->where('service_status_id', ServiceStatusLookup::idFor($status->value));
    }

    /**
     * Limit the query to services matching the given free text term.
     *
     * The slug is included because admins address services by slug in every URL.
     *
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $pattern = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            $query->where('name', 'like', $pattern)
                ->orWhere('slug', 'like', $pattern)
                ->orWhere('description', 'like', $pattern);
        });
    }

    /**
     * Limit the query to services customers are allowed to browse.
     *
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereStatus(ServiceStatus::Published);
    }
}
