<?php

namespace App\Services;

use App\Models\OrderFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderFileDownloader
{
    /**
     * Stream an order file to the browser under its original name.
     */
    public function download(OrderFile $file): BinaryFileResponse
    {
        abort_unless($file->exists(), 404);

        return response()->download(
            Storage::disk('local')->path($file->file_path),
            $file->file_name,
        );
    }
}
