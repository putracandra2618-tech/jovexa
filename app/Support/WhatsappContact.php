<?php

namespace App\Support;

class WhatsappContact
{
    public static function number(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public static function link(?string $value, ?string $message = null): ?string
    {
        $number = self::number($value);

        if ($number === null) {
            return null;
        }

        $link = 'https://wa.me/'.$number;

        if (filled($message)) {
            $link .= '?text='.rawurlencode($message);
        }

        return $link;
    }
}
