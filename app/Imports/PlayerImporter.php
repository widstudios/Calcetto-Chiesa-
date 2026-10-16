<?php

namespace App\Imports;

use App\Enums\SquadRole;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Str;

/**
 * Calciatori delle rose, come il comando rosa:importa ma con più squadre nello stesso file.
 */
class PlayerImporter extends Importer
{
    public function label(): string
    {
        return 'Calciatori';
    }

    public function description(): string
    {
        return 'Un calciatore per riga. Chi è già in rosa (stesso nome, cognome e anno) non viene duplicato; torneo e squadra mancanti vengono creati.';
    }

    public function columns(): array
    {
        return [
            'torneo*' => 'Nome del torneo o della categoria',
            'stagione' => 'Serve solo se lo stesso torneo ha più stagioni',
            'squadra*' => 'Nome della squadra',
            'cognome*' => 'Cognome',
            'nome*' => 'Nome',
            'anno*' => 'Anno di nascita, es. 2013',
            'numero' => 'Numero di maglia',
            'cellulare' => 'Cellulare di contatto, es. 333 123 4567',
            'cellulare2' => 'Secondo cellulare',
            'ruolo' => 'titolare (prima squadra) o panchina; vuoto = titolare',
        ];
    }

    public function example(): array
    {
        return [
            ['Torneo di Primavera', '2026/27', 'Leoni', 'Rossi', 'Mario', 1995, 10, '333 123 4567', '', 'titolare'],
            ['Torneo di Primavera', '2026/27', 'Leoni', 'Verdi', 'Paolo', 1998, 12, '', '', 'panchina'],
        ];
    }

    public function route(): string
    {
        return route('teams.index');
    }

    public function parse(array $row): array
    {
        $role = Str::lower(trim($row['ruolo'] ?? ''));
        $squadRole = match (true) {
            $role === '' || str_starts_with($role, 'titolar') || $role === 'prima squadra' => SquadRole::Titolare,
            str_starts_with($role, 'panchin') => SquadRole::Panchina,
            default => throw new RowError('"ruolo" deve essere titolare o panchina.'),
        };

        return $this->teamColumns($row) + [
            'last_name' => $this->name($this->required($row, 'cognome')),
            'first_name' => $this->name($this->required($row, 'nome')),
            'birth_year' => $this->integer($this->required($row, 'anno'), 'anno', 1940, (int) now()->year),
            'shirt_number' => $this->integer($row['numero'] ?? '', 'numero', 0, 99),
            'phone' => $this->phone($row['cellulare'] ?? '', 'cellulare'),
            'phone_2' => $this->phone($row['cellulare2'] ?? '', 'cellulare2'),
            'squad_role' => $squadRole->value,
            // Il ruolo scritto nel foglio cambia quello di chi è già in rosa; una cella vuota no.
            'role_given' => $role !== '',
        ];
    }

    public function summary(array $data): string
    {
        return "{$data['first_name']} {$data['last_name']} ({$data['birth_year']}) · {$data['squadra']}, "
            .$this->tournamentLabel($data).' · '.SquadRole::from($data['squad_role'])->label()
            .($data['shirt_number'] !== null ? " · n. {$data['shirt_number']}" : '');
    }

    public function exists(array $data): bool
    {
        $team = $this->findTeam($this->findTournament($data['torneo'], $data['stagione']), $data['squadra']);

        return $team !== null && $this->find($team, $data) !== null;
    }

    public function save(array $data): bool
    {
        $team = $this->teamOrCreate($this->tournamentOrCreate($data['torneo'], $data['stagione']), $data['squadra']);
        $player = $this->find($team, $data);

        if ($player === null) {
            $team->players()->create(collect($data)->only(['first_name', 'last_name', 'birth_year', 'phone', 'phone_2', 'shirt_number', 'squad_role'])->all());

            return true;
        }

        $player->update(array_filter([
            'shirt_number' => $data['shirt_number'],
            'phone' => $data['phone'],
            'phone_2' => $data['phone_2'],
            'squad_role' => $data['role_given'] ? $data['squad_role'] : null,
        ], fn ($value) => $value !== null));

        return false;
    }

    private function phone(string $value, string $column): ?string
    {
        $phone = preg_replace('/\s+/', ' ', trim($value));
        if ($phone !== '' && ! preg_match('/^\+?[\d .\/-]{6,30}$/', $phone)) {
            throw new RowError("\"{$column}\" deve essere un numero, es. 333 123 4567.");
        }

        return $phone === '' ? null : $phone;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function find(Team $team, array $data): ?Player
    {
        return $team->players()
            ->whereRaw('lower(first_name) = ? and lower(last_name) = ?', [Str::lower($data['first_name']), Str::lower($data['last_name'])])
            ->where('birth_year', $data['birth_year'])
            ->first();
    }
}
