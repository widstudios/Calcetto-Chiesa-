<?php

namespace Tests\Unit;

use App\Support\CsiCommunique;
use Tests\TestCase;

class CsiCommuniqueTest extends TestCase
{
    /** Un pezzo di testo del PDF, come lo restituisce il parser. */
    private function chunk(float $x, float $y, string $text): array
    {
        return compact('x', 'y', 'text');
    }

    private function page(): array
    {
        return [
            $this->chunk(40, 700, 'Cat. U10 Girone B'),
            $this->chunk(150, 680, 'SQUADRA OSPITANTE'),
            $this->chunk(330, 680, 'SQUADRA OSPITE'),
            $this->chunk(480, 680, 'DATA'),
            $this->chunk(540, 680, 'RISULTATO'),
            // Risultato.
            $this->chunk(40, 660, 'GXC10B1'),
            $this->chunk(120, 660, 'S.'),
            $this->chunk(130, 660, 'Atanasio'),
            $this->chunk(300, 660, 'Bravetta'),
            $this->chunk(450, 660, '12/10/2026'),
            $this->chunk(500, 660, '16:30'),
            $this->chunk(560, 660, '4 - 8'),
            // Nome lungo che va a capo sulla riga sopra.
            $this->chunk(110, 628, 'Oratorio Ss. Pietro e'),
            $this->chunk(40, 620, 'GXC10B2'),
            $this->chunk(120, 620, 'Paolo'),
            $this->chunk(300, 620, 'Regina Pacis'),
            $this->chunk(450, 620, '19/10/2026'),
            $this->chunk(500, 620, '10:00'),
            // Gara rinviata, con il codice spezzato in due pezzi.
            $this->chunk(40, 590, 'GXC10B'),
            $this->chunk(75, 590, '3'),
            $this->chunk(120, 590, 'Escrivà'),
            $this->chunk(300, 590, 'S. Atanasio'),
            $this->chunk(450, 590, 'Rinviata'),
            // Seconda fase ancora da definire.
            $this->chunk(40, 560, 'GXC10F1'),
            $this->chunk(120, 560, '1° Classificata Girone A'),
            $this->chunk(300, 560, '2° Classificata Girone B'),
            // In classifica i codici non ci sono, ma una riga simile non deve diventare una gara.
            $this->chunk(40, 530, 'CLASSIFICA'),
            $this->chunk(40, 510, 'ABC12'),
            $this->chunk(120, 510, 'S. Atanasio'),
            $this->chunk(300, 510, '9'),
        ];
    }

    public function test_it_reads_games_from_the_table(): void
    {
        $games = array_values(CsiCommunique::parsePages([$this->page()]));

        $this->assertCount(3, $games);

        [$played, $wrapped, $postponed] = $games;
        $this->assertSame('GXC10B1', $played['code']);
        $this->assertSame('Under 10', $played['category']);
        $this->assertSame('B', $played['group']);
        $this->assertSame('S. Atanasio', $played['home']);
        $this->assertSame('Bravetta', $played['away']);
        $this->assertSame('2026-10-12 16:30', $played['kickoff']->format('Y-m-d H:i'));
        $this->assertSame([4, 8], [$played['home_score'], $played['away_score']]);

        $this->assertSame('Oratorio Ss. Pietro e Paolo', $wrapped['home']);
        $this->assertSame('Regina Pacis', $wrapped['away']);
        $this->assertNull($wrapped['home_score']);

        $this->assertSame('GXC10B3', $postponed['code']);
        $this->assertSame('Escrivà', $postponed['home']);
        $this->assertTrue($postponed['postponed']);
        $this->assertNull($postponed['kickoff']);
    }

    public function test_a_later_table_completes_the_same_game(): void
    {
        $program = [
            $this->chunk(40, 700, 'Cat. U10 Girone B'),
            $this->chunk(150, 680, '1ª nominata'),
            $this->chunk(330, 680, '2ª nominata'),
            $this->chunk(480, 680, 'Data'),
            $this->chunk(560, 680, 'Ris.'),
            $this->chunk(40, 660, 'GXC10B1'),
            $this->chunk(120, 660, 'S. Atanasio'),
            $this->chunk(300, 660, 'Bravetta'),
            $this->chunk(450, 660, '30 maggio 2026'),
            $this->chunk(520, 660, 'ore 16.30'),
        ];
        $results = $program;
        $results[8] = $this->chunk(560, 660, '3-1');
        unset($results[9]);

        $games = array_values(CsiCommunique::parsePages([$program, array_values($results)]));

        $this->assertCount(1, $games);
        $this->assertSame('2026-05-30 16:30', $games[0]['kickoff']->format('Y-m-d H:i'));
        $this->assertSame([3, 1], [$games[0]['home_score'], $games[0]['away_score']]);
    }

    public function test_names_and_categories_are_normalised(): void
    {
        $this->assertSame('Under 12', CsiCommunique::category('UNDER 12'));
        $this->assertSame('Under 10', CsiCommunique::category('U10'));
        $this->assertSame('Open', CsiCommunique::category('OPEN'));
        $this->assertSame(CsiCommunique::key('S.Atanasio'), CsiCommunique::key('S. Atanasio'));
        $this->assertSame('sanjosemariaescriva', CsiCommunique::key('San Josemaría Escrivà'));
    }
}
