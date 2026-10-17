<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Colori dell'app scelti in Impostazioni: diventano variabili CSS per gestionale e sito pubblico.
 */
class Theme
{
    /** Colori modificabili, con etichetta e valore predefinito (la grafica verde campo di public/css/app.css). */
    public const COLORS = [
        'header_bg' => ['Sfondo dell\'intestazione', '#052e16'],
        'header_text' => ['Testi dell\'intestazione', '#ffffff'],
        'primary' => ['Colore principale (pulsanti e link)', '#15803d'],
        'heading' => ['Titoli delle pagine', '#0f172a'],
        'accent' => ['Colore di risalto (numeri e pulsante in evidenza)', '#f59e0b'],
    ];

    /**
     * Combinazioni pronte. San Nicola: rosso del logo (#ed1b24) appena più scuro,
     * perché quello originale su bianco non è abbastanza leggibile per testi piccoli.
     */
    public const PRESETS = [
        'verde' => [
            'label' => 'Verde campo (predefinito)',
            'colors' => ['header_bg' => '#052e16', 'header_text' => '#ffffff', 'primary' => '#15803d', 'heading' => '#0f172a', 'accent' => '#f59e0b'],
        ],
        'san-nicola' => [
            'label' => 'San Nicola: bianco e rosso',
            'colors' => ['header_bg' => '#ffffff', 'header_text' => '#d71920', 'primary' => '#d71920', 'heading' => '#d71920', 'accent' => '#f59e0b'],
        ],
    ];

    /** Sfondo delle pagine (--bg) e delle schede (--surface), su cui si misurano i contrasti. */
    public const PAGE_BG = '#f3f6f4';

    public const SURFACE = '#ffffff';

    /** Colore attuale: quello salvato oppure il predefinito. */
    public static function color(string $key): string
    {
        return Setting::get('color_'.$key) ?? self::COLORS[$key][1];
    }

    /** @return array<string, string> */
    public static function colors(): array
    {
        return collect(self::COLORS)->mapWithKeys(fn ($c, $key) => [$key => self::color($key)])->all();
    }

    /**
     * Controlli di leggibilità (WCAG): testo normale almeno 4,5:1, testo grande almeno 3:1.
     *
     * @param  array<string, string>  $c
     * @return array<string, string> campo => messaggio, vuoto se tutto si legge bene
     */
    public static function contrastErrors(array $c): array
    {
        $checks = [
            'header_text' => [$c['header_text'], $c['header_bg'], 4.5, 'I testi dell\'intestazione si leggono male su quello sfondo'],
            'primary' => [$c['primary'], self::SURFACE, 4.5, 'Il colore principale è troppo chiaro: su bianco i link e le scritte bianche dei pulsanti non si leggono'],
            'heading' => [$c['heading'], self::PAGE_BG, 3, 'I titoli si leggono male sullo sfondo chiaro delle pagine'],
            'accent' => [$c['accent'], self::mix($c['primary'], '#000000', .7), 3, 'Il colore di risalto si vede poco sulla fascia scura del sito pubblico'],
        ];

        $errors = [];
        foreach ($checks as $field => [$fg, $bg, $min, $message]) {
            $ratio = self::contrast($fg, $bg);
            if ($ratio < $min) {
                $errors[$field] = sprintf('%s (contrasto %s:1, serve almeno %s:1).', $message, number_format($ratio, 1, ',', ''), number_format($min, 1, ',', ''));
            }
        }

        return $errors;
    }

    /** Variabili CSS da aggiungere alla pagina, oppure null se si usano i colori predefiniti. */
    public static function css(): ?string
    {
        $c = self::colors();
        if ($c === self::PRESETS['verde']['colors']) {
            return null;
        }

        $deep = self::mix($c['primary'], '#000000', .7);
        $onAccent = collect([$deep, '#0f172a', '#ffffff'])
            ->first(fn ($ink) => self::contrast($ink, $c['accent']) >= 4.5) ?? '#ffffff';
        // Intestazione scura: leggera sfumatura come quella verde; chiara: tinta unita.
        $headerEnd = self::luminance($c['header_bg']) < .2 ? self::mix($c['header_bg'], '#ffffff', .15) : $c['header_bg'];

        $vars = [
            '--primary' => $c['primary'],
            '--primary-strong' => self::mix($c['primary'], '#000000', .18),
            '--primary-deep' => $deep,
            '--primary-soft' => self::mix($c['primary'], '#ffffff', .88),
            '--primary-line' => self::mix($c['primary'], '#ffffff', .7),
            '--primary-rgb' => self::rgb($c['primary']),
            '--accent' => $c['accent'],
            '--accent-soft' => self::mix($c['accent'], '#ffffff', .85),
            '--accent-strong' => self::mix($c['accent'], '#000000', .12),
            '--accent-ink' => self::mix($c['accent'], '#000000', .6),
            '--on-accent' => $onAccent,
            '--header-bg' => $c['header_bg'],
            '--header-bg-2' => $headerEnd,
            '--header-text' => $c['header_text'],
            '--header-text-rgb' => self::rgb($c['header_text']),
            '--heading' => $c['heading'],
        ];

        return ':root{'.collect($vars)->map(fn ($v, $k) => "$k:$v")->implode(';').'}';
    }

    /** Rapporto di contrasto WCAG fra due colori #rrggbb (da 1 a 21). */
    public static function contrast(string $a, string $b): float
    {
        [$hi, $lo] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($hi + .05) / ($lo + .05);
    }

    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function ($v) {
            $v /= 255;

            return $v <= .03928 ? $v / 12.92 : (($v + .055) / 1.055) ** 2.4;
        }, self::channels($hex));

        return .2126 * $r + .7152 * $g + .0722 * $b;
    }

    /** Mescola $hex con $with: $amount 0 = $hex, 1 = $with. */
    public static function mix(string $hex, string $with, float $amount): string
    {
        $a = self::channels($hex);
        $b = self::channels($with);

        return '#'.implode('', array_map(fn ($i) => sprintf('%02x', (int) round($a[$i] + ($b[$i] - $a[$i]) * $amount)), [0, 1, 2]));
    }

    private static function rgb(string $hex): string
    {
        return implode(',', self::channels($hex));
    }

    /** @return array{int, int, int} */
    private static function channels(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }
}
