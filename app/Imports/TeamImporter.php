<?php

namespace App\Imports;

use App\Enums\Role;
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
            'allenatore' => 'Nome dell\'allenatore, es. Mario Rossi',
            'email_allenatore' => 'Email dell\'account allenatore già caricato: potrà gestire la rosa',
        ];
    }

    public function example(): array
    {
        return [
            ['Torneo di Primavera', '2026/27', 'Leoni', 'Mario Rossi', ''],
            ['Torneo di Primavera', '2026/27', 'Aquile', 'Luca Bianchi', 'luca.bianchi@example.com'],
        ];
    }

    public function route(): string
    {
        return route('teams.index');
    }

    public function parse(array $row): array
    {
        $data = $this->teamColumns($row) + ['allenatore' => trim($row['allenatore'] ?? '') ?: null, 'coach_id' => null];

        $email = Str::lower(trim($row['email_allenatore'] ?? ''));
        if ($email !== '') {
            $coach = User::whereRaw('lower(email) = ?', [$email])->where('role', Role::Allenatore)->first();
            $data['coach_id'] = $coach?->id ?? throw new RowError("nessun allenatore con email {$email}: carica prima gli allenatori.");
        }

        return $data;
    }

    public function summary(array $data): string
    {
        return $data['squadra'].' · '.$this->tournamentLabel($data).($data['allenatore'] ? " · all. {$data['allenatore']}" : '');
    }

    public function exists(array $data): bool
    {
        return $this->findTeam($this->findTournament($data['torneo'], $data['stagione']), $data['squadra']) !== null;
    }

    public function save(array $data): bool
    {
        $team = $this->teamOrCreate($this->tournamentOrCreate($data['torneo'], $data['stagione']), $data['squadra']);
        $team->update(array_filter(['coach_name' => $data['allenatore']]));
        if ($data['coach_id'] !== null) {
            // Si aggiunge agli allenatori già presenti, senza toglierne nessuno.
            $team->coaches()->syncWithoutDetaching([$data['coach_id']]);
        }

        return $team->wasRecentlyCreated;
    }
}
