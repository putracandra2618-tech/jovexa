<?php

namespace App\Models;

use Database\Factories\RevisionRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['order_id', 'requested_by', 'revision_number', 'note'])]
class RevisionRequest extends Model
{
    /** @use HasFactory<RevisionRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'order_revision_requests';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @param  Builder<RevisionRequest>  $query
     * @return Builder<RevisionRequest>
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest()->latest('id');
    }

    /**
     * Show only the message the customer or staff actually wrote.
     */
    protected function cleanNote(): Attribute
    {
        return Attribute::get(fn (): string => trim((string) $this->note))->withoutObjectCaching();
    }
}
