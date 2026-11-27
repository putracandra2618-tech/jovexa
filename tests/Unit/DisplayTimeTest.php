<?php

namespace Tests\Unit;

use App\Support\DisplayTime;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DisplayTimeTest extends TestCase
{
    public function test_it_formats_utc_time_in_wib(): void
    {
        $this->assertSame(
            '27 Nov 2026 07:00 WIB',
            DisplayTime::format(Carbon::parse('2026-11-27 00:00:00', 'UTC')),
        );
    }

    public function test_it_uses_a_dash_for_missing_time(): void
    {
        $this->assertSame('—', DisplayTime::format(null));
    }
}
