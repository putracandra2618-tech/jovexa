<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role_id', 'is_online'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_online' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * The role assigned to the user.
     *
     * @return BelongsTo<Role, $this>
     */
    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Expose the role as its enum so existing callers keep working.
     */
    protected function role(): Attribute
    {
        return Attribute::get(fn (): ?UserRole => $this->roleRecord?->enum())
            ->withoutObjectCaching();
    }

    /**
     * The orders placed by this customer.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The orders this worker has claimed.
     *
     * @return HasMany<Order, $this>
     */
    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'worker_id');
    }

    /**
     * Limit the query to users holding the given role.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeWhereRole(Builder $query, UserRole $role): Builder
    {
        return $query->where('role_id', Role::idFor($role->value));
    }

    /**
     * Limit the query to users matching the given free text term.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $pattern = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            $query->where('name', 'like', $pattern)
                ->orWhere('email', 'like', $pattern);
        });
    }

    /**
     * Determine whether the user has the given role.
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Determine whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    /**
     * Determine whether the user can manage services and every order.
     */
    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Admin) || $this->hasRole(UserRole::SuperAdmin);
    }

    /**
     * Determine whether the user handles claimed orders.
     */
    public function isWorker(): bool
    {
        return $this->hasRole(UserRole::Worker);
    }

    /**
     * Determine whether the user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->hasRole(UserRole::Customer);
    }
}
