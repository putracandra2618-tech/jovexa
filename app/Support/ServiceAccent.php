<?php

namespace App\Support;

/**
 * Visual treatment shared by the customer facing views.
 *
 * The playful palette lives in CSS, so a view only needs the icon and tone name
 * to pick the right pastel chip.
 */
class ServiceAccent
{
    /**
     * Ordered keyword to accent pairs, the first match wins.
     *
     * @var list<array{keywords: list<string>, icon: string, tone: string}>
     */
    private const RULES = [
        [
            'keywords' => ['skripsi', 'thesis', 'makalah', 'artikel', 'esai', 'essay', 'tugas akhir', 'laporan'],
            'icon' => 'bi-file-earmark-text',
            'tone' => 'mint',
        ],
        [
            'keywords' => ['ppt', 'powerpoint', 'presentasi', 'slide', 'deck'],
            'icon' => 'bi-easel2',
            'tone' => 'coral',
        ],
        [
            'keywords' => ['desain', 'design', 'logo', 'poster', 'banner', 'canva', 'figur', 'cover'],
            'icon' => 'bi-palette',
            'tone' => 'mango',
        ],
        [
            'keywords' => ['landing', 'web', 'website', 'coding', 'program', 'aplikasi', 'app', 'frontend', 'backend'],
            'icon' => 'bi-code-slash',
            'tone' => 'sky',
        ],
        [
            'keywords' => ['data', 'spss', 'statistik', 'analisis', 'statis', 'excel', 'spreadsheet'],
            'icon' => 'bi-bar-chart-line',
            'tone' => 'lilac',
        ],
        [
            'keywords' => ['video', 'editing', 'edit video', 'montage', 'animasi', 'motion'],
            'icon' => 'bi-camera-reels',
            'tone' => 'coral',
        ],
        [
            'keywords' => ['translate', 'terjemah', 'bahasa'],
            'icon' => 'bi-translate',
            'tone' => 'sky',
        ],
        [
            'keywords' => ['akuntansi', 'pajak', 'keuangan', 'laporan keuangan'],
            'icon' => 'bi-calculator',
            'tone' => 'mint',
        ],
    ];

    /**
     * The fallback used when no keyword matches.
     *
     * @var array{icon: string, tone: string}
     */
    private const FALLBACK = ['icon' => 'bi-stars', 'tone' => 'mint'];

    /**
     * Resolve the icon and pastel tone for the given service name.
     *
     * @return array{icon: string, tone: string}
     */
    public static function for(mixed $service): array
    {
        $name = self::normalize(self::nameOf($service));

        if ($name === '') {
            return self::FALLBACK;
        }

        foreach (self::RULES as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($name, $keyword)) {
                    return ['icon' => $rule['icon'], 'tone' => $rule['tone']];
                }
            }
        }

        return self::FALLBACK;
    }

    /**
     * Read the service name from a model or a plain string.
     */
    private static function nameOf(mixed $service): string
    {
        if (is_string($service)) {
            return $service;
        }

        if (is_object($service) && isset($service->name)) {
            return (string) $service->name;
        }

        return '';
    }

    /**
     * Lowercase the name so keyword matching stays case insensitive.
     */
    private static function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
