<?php

namespace App\Console\Commands;

use App\Services\TripayClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('tripay:sync-channels')]
#[Description('Menyegarkan daftar channel pembayaran Tripay')]
class TripaySyncChannels extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TripayClient $tripay): int
    {
        Cache::forget(TripayClient::CHANNEL_CACHE_KEY);

        $channels = $tripay->availableChannels();

        $this->info(sprintf('Berhasil menyegarkan %d channel pembayaran.', count($channels)));

        foreach ($channels as $code => $label) {
            $this->line(sprintf('- %s (%s)', $label, $code));
        }

        return self::SUCCESS;
    }
}
