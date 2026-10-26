<?php

namespace App\Support;

/**
 * Divide il regolamento scritto dall'admin in paragrafi ed elenchi puntati, da mostrare sul sito.
 * Una riga vuota separa i paragrafi; le righe che iniziano con "-" o "*" formano un elenco.
 */
class RulesText
{
    /**
     * @return list<array{type: 'p'|'ul', lines: list<string>}>
     */
    public static function blocks(?string $text): array
    {
        $blocks = [];

        foreach (preg_split('/\R\s*\R/u', trim((string) $text)) as $chunk) {
            foreach (preg_split('/\R/u', trim($chunk)) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $isItem = preg_match('/^[-*•]\s*(.*)$/u', $line, $m) === 1;
                $type = $isItem ? 'ul' : 'p';
                $last = array_key_last($blocks);

                // Righe di fila dello stesso tipo stanno nello stesso paragrafo o elenco.
                if ($last !== null && $blocks[$last]['type'] === $type && ! ($blocks[$last]['closed'] ?? false)) {
                    $blocks[$last]['lines'][] = $isItem ? $m[1] : $line;
                } else {
                    $blocks[] = ['type' => $type, 'lines' => [$isItem ? $m[1] : $line]];
                }
            }
            // La riga vuota chiude il blocco: il paragrafo dopo ne inizia uno nuovo.
            if ($blocks !== []) {
                $blocks[array_key_last($blocks)]['closed'] = true;
            }
        }

        return array_map(fn ($b) => ['type' => $b['type'], 'lines' => $b['lines']], $blocks);
    }
}
