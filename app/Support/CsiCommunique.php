<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

/**
 * Legge un Comunicato Ufficiale del CSI Roma (Oratorio Cup, Giocare per Credere, campionati)
 * e ne ricava le partite: codice gara, categoria, girone, squadre, data e ora, risultato.
 *
 * I comunicati sono tabelle: si ricostruiscono le righe dalla posizione dei pezzi di testo
 * nel PDF e si dividono le colonne con le intestazioni ("SQUADRA OSPITANTE", "1ª nominata"...).
 * I marcatori nei comunicati non ci sono.
 */
class CsiCommunique
{
    private const MONTHS = ['gennaio' => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4, 'maggio' => 5, 'giugno' => 6,
        'luglio' => 7, 'agosto' => 8, 'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12];

    /** Codice gara: GXC10B3, CAC615, GXCOPA4... */
    private const CODE = '/^[A-Z]{2,4}\d*[A-Z0-9]*\d$/';

    /**
     * @return array<string, array{code: string, category: ?string, group: ?string, home: string, away: string, kickoff: ?Carbon, postponed: ?bool, home_score: ?int, away_score: ?int}>
     */
    public static function parseFile(string $path): array
    {
        $pages = [];
        foreach ((new Parser)->parseFile($path)->getPages() as $page) {
            $pages[] = array_map(fn ($d) => ['x' => (float) $d[0][4], 'y' => (float) $d[0][5], 'text' => (string) $d[1]], $page->getDataTm());
        }

        return self::parsePages($pages);
    }

    /**
     * @param  list<list<array{x: float, y: float, text: string}>>  $pages  pezzi di testo con la loro posizione, pagina per pagina
     * @return array<string, array{code: string, category: ?string, group: ?string, home: string, away: string, kickoff: ?Carbon, postponed: ?bool, home_score: ?int, away_score: ?int}>
     */
    public static function parsePages(array $pages): array
    {
        $games = [];
        $category = $group = null;
        $columns = null;

        // Nelle classifiche ogni nome è un pezzo unico, con gli spazi giusti: serve a correggere i nomi spezzati.
        $spelling = [];
        foreach (array_merge(...$pages ?: [[]]) as $chunk) {
            $text = trim($chunk['text']);
            if (mb_strlen($text) >= 3) {
                $spelling[self::key($text)] ??= $text;
            }
        }

        foreach ($pages as $chunks) {
            $rows = self::rows($chunks);

            foreach ($rows as $i => $row) {
                $line = self::join($row);

                // Intestazioni di categoria: "Cat. U10 Girone B", oppure "CATEGORIA" seguito da "UNDER 10".
                if (preg_match('/^Cat\.\s*(.+?)(?:\s+Girone\s+(.+))?$/iu', $line, $m)) {
                    [$category, $group] = [self::category($m[1]), isset($m[2]) ? Str::upper(trim($m[2])) : null];

                    continue;
                }
                if (preg_match('/^(UNDER\s*\d+|OPEN(?:\s+(?:MASCHILE|FEMMINILE))?|JUNIORES|ALLIEVI|RAGAZZI|AMATORI)$/iu', $line, $m) && $i > 0 && Str::upper(self::join($rows[$i - 1])) === 'CATEGORIA') {
                    [$category, $group] = [self::category($m[1]), null];

                    continue;
                }
                if (preg_match('/^GIRONE\s+([A-Z]|UNICO)$/iu', $line, $m)) {
                    $group = Str::upper($m[1]);

                    continue;
                }

                if ($header = self::header($row)) {
                    $columns = $header;

                    continue;
                }
                // Classifiche e sanzioni non sono partite: si aspetta la prossima tabella di gare.
                if (preg_match('/CLASSIFICA|^SANZIONI|^Ammonizioni:|^Squadra\s+(P\.ti|PU)\b|^SQUADRA\s+PU\b/u', $line)) {
                    $columns = null;

                    continue;
                }

                // A volte il codice è spezzato in due pezzi: "GXC13C" + "1".
                if (isset($row[1]) && preg_match('/^[A-Z0-9]{1,2}$/', trim($row[1]['text'])) && preg_match(self::CODE, trim($row[0]['text']).trim($row[1]['text']))) {
                    $row = [['x' => $row[0]['x'], 'y' => $row[0]['y'], 'text' => trim($row[0]['text']).trim($row[1]['text'])], ...array_slice($row, 2)];
                }

                $code = trim($row[0]['text']);
                if (! $columns || ! preg_match(self::CODE, $code)) {
                    continue;
                }

                $cells = ['home' => [], 'away' => [], 'right' => []];
                foreach (array_slice($row, 1) as $chunk) {
                    $cells[self::column($chunk['x'], $columns)][] = $chunk;
                }

                // Nomi lunghi vanno a capo: le righe vicine senza codice completano le colonne delle squadre.
                foreach ([$i - 1, $i + 1] as $j) {
                    $near = $rows[$j] ?? null;
                    if (! $near || abs($near[0]['y'] - $row[0]['y']) > 12 || preg_match(self::CODE, trim($near[0]['text'])) || self::header($near)) {
                        continue;
                    }
                    foreach ($near as $chunk) {
                        $column = self::column($chunk['x'], $columns);
                        if ($column === 'right' || $chunk['x'] < $columns['home_min']) {
                            continue 2;
                        }
                    }
                    foreach ($near as $chunk) {
                        $column = self::column($chunk['x'], $columns);
                        $j < $i ? array_unshift($cells[$column], $chunk + ['space' => true]) : $cells[$column][] = $chunk + ['space' => true];
                    }
                }

                $home = self::clean(self::join($cells['home']));
                $away = self::clean(self::join($cells['away']));
                $home = $spelling[self::key($home)] ?? $home;
                $away = $spelling[self::key($away)] ?? $away;
                // Righe della seconda fase ancora da definire: "1° Classificata Girone A", "Vincitrice gara ...".
                if ($home === '' || $away === '' || preg_match('/classificat|vincitric|perdente/iu', $home.' '.$away)) {
                    continue;
                }

                $right = self::join($cells['right'], true);
                [$kickoff, $homeScore, $awayScore] = self::details($right);
                $postponed = (bool) preg_match('/rinviat/iu', $right) ?: null;

                $game = compact('code', 'category', 'group', 'home', 'away', 'kickoff', 'postponed') + ['home_score' => $homeScore, 'away_score' => $awayScore];
                // La stessa gara può tornare (programma, risultati, recuperi): si completa quello che manca.
                // Il CSI a volte ripete un codice per due gare diverse, perciò contano anche le squadre.
                $key = $code.'|'.self::key($home).'|'.self::key($away);
                $games[$key] = isset($games[$key]) ? array_merge($games[$key], array_filter($game, fn ($v) => $v !== null)) : $game;
            }
        }

        return $games;
    }

    /**
     * Raggruppa i pezzi di testo in righe (stessa altezza), dall'alto in basso e da sinistra a destra.
     *
     * @param  list<array{x: float, y: float, text: string}>  $chunks
     * @return list<list<array{x: float, y: float, text: string}>>
     */
    private static function rows(array $chunks): array
    {
        $rows = [];
        foreach ($chunks as $chunk) {
            if (trim($chunk['text']) === '') {
                continue;
            }
            $key = null;
            foreach (array_keys($rows) as $y) {
                if (abs($y - $chunk['y']) <= 2) {
                    $key = $y;
                    break;
                }
            }
            $rows[$key ?? (string) $chunk['y']][] = $chunk;
        }

        uksort($rows, fn ($a, $b) => (float) $b <=> (float) $a);

        return array_values(array_map(function ($row) {
            usort($row, fn ($a, $b) => $a['x'] <=> $b['x']);

            return $row;
        }, $rows));
    }

    /**
     * Unisce i pezzi di una cella. Di solito nel PDF gli spazi ci sono già; manca quando una parola
     * nuova comincia con la maiuscola ("Ss" + "Trinità"), o tra numeri lontani (ore, risultati).
     *
     * @param  list<array{x: float, text: string, space?: bool}>  $chunks
     */
    private static function join(array $chunks, bool $numeric = false): string
    {
        $text = '';
        $end = null;
        foreach ($chunks as $chunk) {
            $newWord = ! $numeric && preg_match('/[\p{L}.]$/u', $text) && preg_match('/^\p{Lu}/u', $chunk['text']);
            if ($text !== '' && (($chunk['space'] ?? false) || $newWord || ($numeric && $end !== null && $chunk['x'] - $end > 8))) {
                $text .= ' ';
            }
            $text .= $chunk['text'];
            $end = $chunk['x'] + mb_strlen($chunk['text']) * 5;
        }

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** @return array{home_min: float, away: float, right: float}|null */
    private static function header(array $row): ?array
    {
        // "SQUADRA OSPITANTE" / "SQUADRA OSPITE", oppure "1ª nominata" / "2ª nominata"; a volte spezzati in più pezzi.
        $teams = array_values(array_filter($row, fn ($c) => preg_match('/^(SQUADRA|[12]ª\s*nominata)/iu', trim($c['text']))));
        $right = collect($row)->filter(fn ($c) => preg_match('/^(DATA|RISULTATO|Ris\.)$/iu', trim($c['text'])))->min('x');
        if (count($teams) !== 2 || $right === null) {
            return null;
        }
        [$home, $away] = [$teams[0]['x'], $teams[1]['x']];

        // I nomi sono centrati sotto l'intestazione: un nome lungo comincia prima dell'intestazione stessa.
        return ['home_min' => $home - 70, 'away' => ($home + $away) / 2 + 15, 'right' => $right - 60];
    }

    private static function column(float $x, array $columns): string
    {
        return match (true) {
            $x >= $columns['right'] => 'right',
            $x >= $columns['away'] => 'away',
            default => 'home',
        };
    }

    /** @return array{0: ?Carbon, 1: ?int, 2: ?int} */
    private static function details(string $text): array
    {
        $date = null;
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $text, $m)) {
            $date = [(int) $m[3], (int) $m[2], (int) $m[1]];
        } elseif (preg_match('/(\d{1,2})\s*('.implode('|', array_keys(self::MONTHS)).')\s*(\d{4})/iu', $text, $m)) {
            $date = [(int) $m[3], self::MONTHS[Str::lower($m[2])], (int) $m[1]];
        }
        $rest = $date ? str_replace($m[0], ' ', $text) : $text;

        $time = null;
        if (preg_match('/\b(\d{1,2})\s*[:.]\s*(\d{2})\b/', $rest, $t)) {
            $time = [(int) $t[1], (int) $t[2]];
            $rest = str_replace($t[0], ' ', $rest);
        }

        $kickoff = $date && checkdate($date[1], $date[2], $date[0])
            ? Carbon::create($date[0], $date[1], $date[2], $time[0] ?? 0, $time[1] ?? 0, 0, config('app.timezone'))
            : null;

        if (preg_match('/\b(\d{1,2})\s*[-–]\s*(\d{1,2})\b/u', $rest, $s)) {
            return [$kickoff, (int) $s[1], (int) $s[2]];
        }

        return [$kickoff, null, null];
    }

    /** "U10", "UNDER 10", "U 10" diventano "Under 10"; "OPEN" diventa "Open". */
    public static function category(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/^U(?:NDER)?\s*-?\s*(\d+)$/iu', $raw, $m)) {
            return 'Under '.(int) $m[1];
        }

        return Str::ucfirst(Str::lower($raw));
    }

    /** Nome senza spazi, punti e accenti, per confrontare due modi di scriverlo. */
    public static function key(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($name)));
    }

    private static function clean(string $name): string
    {
        return trim(preg_replace('/\s*\(\*\)$/u', '', $name));
    }
}
