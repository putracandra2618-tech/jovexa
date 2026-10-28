<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Lookup\Lookup;

class Role extends Lookup
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * Map the stored role code onto its enum.
     */
    public function enum(): ?UserRole
    {
        return UserRole::tryFrom($this->code);
    }
}
