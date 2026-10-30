<?php

namespace App\Imports;

use App\Enums\TournamentFormat;
use App\Models\User;
use Illuminate\Support\Str;

class TeamImporter extends Importer
{
    public function label(): string
    {
        return 'Squadre';
    }

    public function description(): string
    {
        return 'Una squadra per riga. Se il torneo non c\'è ancora viene creato.';
    }

    public function columns(): array
    {
        return [
            'torneo*' => 'Nome del torneo',
            'stagione' => 'Serve solo se lo stesso torneo ha più stagioni',
            'squadra*' => 'Nome della squadra',
            'girone' => 'Lettera del girone (A, B, C...), solo nei tornei a gironi',
            'allenatore' => 'Nome dell\'allenatore, es. Mario Rossi',
            'email_allenatore' => 'Email dell\'account allenatore già caricato: potrà gestire la rosa',
        ];
    }

    public function example(): array
    {
        return [
            ['Torneo di Primavera', '2026/27', 'Leoni', '', 'Mario Rossi', ''],
            ['Torneo di Primavera', '2026/27', 'Aquile', '', 'Luca Bianchi', 'luca.bianchi@example.com'],
        ];
    }

    public function route(): string
    {
        return route('teams.index');
    }

    public function parse(array $row): array
    {
        $data = $this->teamColumns($row) + ['allenatore' => trim($row['allenatore'] ?? '') ?: null, 'coach_id' => null];

        $group = Str::upper(trim($row['girone'] ?? ''));
        $group = trim(Str::after($group, 'GIRONE'));
        if ($group !== '' && ! preg_match('/^[A-H]$/', $group)) {
            throw new RowError("girone \"{$row['girone']}\" non valido: scrivi una lettera da A a H.");
        }
        $data['girone'] = $group ?: null;

        $email = Str::lower(trim($row['email_allenatore'] ?? ''));
        if ($email !== '') {
            $coach = User::whereRaw('lower(email) = ?', [$email])->coaches()->first();
            $data['coach_id'] = $coach?->id ?? throw new RowError("nessun allenatore con email {$email}: carica prima gli allenatori.");
        }

        return $data;
    }

    public function summary(array $data): string
    {
        return $data['squadra'].' · '.$this->tournamentLabel($data).($data['girone'] ? " · girone {$data['girone']}" : '').($data['allenatore'] ? " · all. {$data['allenatore']}" : '');
    }

    public function exists(array $data): bool
    {
        return $this->findTeam($this->findTournament($data['torneo'], $data['stagione']), $data['squadra']) !== null;
    }

    public function save(array $data): bool
    {
        $tournament = $this->tournamentOrCreate($data['torneo'], $data['stagione']);
        $team = $this->teamOrCreate($tournament, $data['squadra']);
        $team->update(array_filter(['coach_name' => $data['allenatore']]));

        if ($data['girone']) {
            // Una squadra con il girone rende il torneo "a gironi", con abbastanza gironi per quella lettera.
            $needed = ord($data['girone']) - ord('A') + 1;
            if (! $tournament->hasGroups() || count($tournament->groupNames()) < $needed) {
                $tournament->update(['format' => TournamentFormat::Gironi, 'groups_count' => max($needed, $tournament->hasGroups() ? $tournament->groups_count : 0)]);
            }
            $team->update(['group_name' => $data['girone']]);
        }
        if ($data['coach_id'] !== null) {
            // Si aggiunge agli allenatori già presenti, senza toglierne nessuno.
            $team->coaches()->syncWithoutDetaching([$data['coach_id']]);
        }

        return $team->wasRecentlyCreated;
    }
}
