<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DisplayTime
{
    public static function format(CarbonInterface|string|null $value): string
    {
        if ($value === null) {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? clone $value : Carbon::parse($value);

        return $date->setTimezone(config('app.timezone', 'Asia/Jakarta'))
            ->format('d M Y H:i').' WIB';
    }
}
