<?php

namespace App\Imports;

use App\Models\Tournament;
use Illuminate\Support\Str;

class TournamentImporter extends Importer
{
    public function label(): string
    {
        return 'Tornei';
    }

    public function description(): string
    {
        return 'Un torneo o una categoria per riga. Lo stesso nome con la stessa stagione viene aggiornato, non duplicato.';
    }

    public function columns(): array
    {
        return [
            'nome*' => 'Nome del torneo, es. Under 14',
            'stagione' => 'Es. 2026/27',
            'inizio' => 'Data di inizio, es. 01/10/2026',
            'fine' => 'Data di fine',
            'descrizione' => 'Testo libero',
        ];
    }

    public function example(): array
    {
        return [
            ['Torneo di Primavera', '2026/27', '01/03/2027', '30/05/2027', 'Calcio a 5, gironi e finali'],
            ['Under 14', '2026/27', '', '', ''],
        ];
    }

    public function route(): string
    {
        return route('tournaments.index');
    }

    public function parse(array $row): array
    {
        $data = [
            'name' => $this->required($row, 'nome'),
            'season' => trim($row['stagione'] ?? '') ?: null,
            'starts_on' => $this->date($row['inizio'] ?? '', 'inizio'),
            'ends_on' => $this->date($row['fine'] ?? '', 'fine'),
            'description' => trim($row['descrizione'] ?? '') ?: null,
        ];
        if ($data['starts_on'] && $data['ends_on'] && $data['ends_on'] < $data['starts_on']) {
            throw new RowError('la data di fine viene prima di quella di inizio.');
        }

        return $data;
    }

    public function summary(array $data): string
    {
        return $data['name'].($data['season'] ? " ({$data['season']})" : '');
    }

    public function exists(array $data): bool
    {
        return $this->find($data) !== null;
    }

    public function save(array $data): bool
    {
        $tournament = $this->find($data) ?? new Tournament;
        // Le celle vuote non cancellano quello che c'è già.
        $tournament->fill(array_filter($data, fn ($value) => $value !== null))->save();

        return $tournament->wasRecentlyCreated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function find(array $data): ?Tournament
    {
        return Tournament::whereRaw('lower(name) = ?', [Str::lower($data['name'])])
            ->where('season', $data['season'])
            ->first();
    }
}
