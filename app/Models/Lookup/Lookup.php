<?php

namespace App\Models\Lookup;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

abstract class Lookup extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['code', 'name'];

    /**
     * Resolve the primary key for an existing lookup code.
     */
    public static function idFor(string $code): int
    {
        $id = static::query()->where('code', $code)->value('id');

        if ($id === null) {
            throw new RuntimeException(sprintf(
                'Lookup "%s" tidak ditemukan pada tabel %s.',
                $code,
                (new static)->getTable(),
            ));
        }

        return (int) $id;
    }

    /**
     * Resolve a lookup code, creating the row when it does not exist yet.
     */
    public static function ensureIdFor(string $code, ?string $name = null): int
    {
        return (int) static::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name ?? $code],
        )->getKey();
    }
}
