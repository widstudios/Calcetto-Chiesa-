<?php

namespace App\Imports;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Str;

/**
 * Calendario e risultati. Le squadre devono già esistere nel torneo, così un nome
 * scritto male non crea una squadra nuova.
 */
class GameImporter extends Importer
{
    public function label(): string
    {
        return 'Partite';
    }

    public function description(): string
    {
        return 'Una partita per riga, con il risultato se è già stata giocata. La stessa sfida nella stessa giornata viene aggiornata.';
    }

    public function columns(): array
    {
        return [
            'torneo*' => 'Nome del torneo',
            'stagione' => 'Serve solo se lo stesso torneo ha più stagioni',
            'giornata' => 'Numero della giornata',
            'data' => 'Es. 18/10/2026',
            'ora' => 'Es. 20:30',
            'casa*' => 'Squadra di casa, già caricata nel torneo',
            'trasferta*' => 'Squadra in trasferta, già caricata nel torneo',
            'campo' => 'Dove si gioca',
            'gol_casa' => 'Gol della squadra di casa, se giocata',
            'gol_trasferta' => 'Gol della squadra in trasferta, se giocata',
            'rinviata' => 'Scrivi "sì" se la partita è rinviata',
        ];
    }

    public function example(): array
    {
        return [
            ['Torneo di Primavera', '2026/27', 1, '18/10/2026', '20:30', 'Leoni', 'Aquile', 'Campo parrocchiale', 3, 2, ''],
            ['Torneo di Primavera', '2026/27', 2, '25/10/2026', '21:00', 'Aquile', 'Leoni', 'Campo parrocchiale', '', '', ''],
        ];
    }

    public function route(): string
    {
        return route('games.index');
    }

    public function parse(array $row): array
    {
        $data = [
            'torneo' => $this->required($row, 'torneo'),
            'stagione' => trim($row['stagione'] ?? '') ?: null,
            'casa' => $this->required($row, 'casa'),
            'trasferta' => $this->required($row, 'trasferta'),
            'matchday' => $this->integer($row['giornata'] ?? '', 'giornata', 1, 99),
            'venue' => trim($row['campo'] ?? '') ?: null,
            'home_score' => $this->integer($row['gol_casa'] ?? '', 'gol_casa', 0, 99),
            'away_score' => $this->integer($row['gol_trasferta'] ?? '', 'gol_trasferta', 0, 99),
        ];

        // Una cella data di Excel può contenere anche l'orario.
        $day = $this->date($row['data'] ?? '', 'data');
        $time = $this->time(($row['ora'] ?? '') ?: (str_contains($row['data'] ?? '', ' ') ? Str::after($row['data'], ' ') : ''), 'ora');
        if ($time !== null && $day === null) {
            throw new RowError('c\'è l\'ora ma manca la data.');
        }
        $data['kickoff_at'] = $day ? $day.' '.($time ?? '00:00').':00' : null;

        if (($data['home_score'] === null) !== ($data['away_score'] === null)) {
            throw new RowError('scrivi i gol di entrambe le squadre, o di nessuna.');
        }
        $postponed = in_array(Str::lower(Str::ascii(trim($row['rinviata'] ?? ''))), ['si', 's', 'x', '1'], true);
        $data['status'] = match (true) {
            $postponed => GameStatus::Rinviata->value,
            $data['home_score'] !== null => GameStatus::Giocata->value,
            default => GameStatus::Programmata->value,
        };
        if ($postponed) {
            $data['home_score'] = $data['away_score'] = null;
        }

        [$home, $away] = $this->teams($data);
        if ($home->is($away)) {
            throw new RowError('una squadra non può giocare contro sé stessa.');
        }

        return $data;
    }

    public function summary(array $data): string
    {
        $result = $data['status'] === GameStatus::Giocata->value ? " {$data['home_score']}-{$data['away_score']}" : '';

        return ($data['matchday'] ? "{$data['matchday']}ª giornata · " : '')
            ."{$data['casa']} - {$data['trasferta']}{$result}"
            .($data['kickoff_at'] ? ' · '.date('d/m/Y H:i', strtotime($data['kickoff_at'])) : '')
            .($data['status'] === GameStatus::Rinviata->value ? ' · rinviata' : '');
    }

    public function exists(array $data): bool
    {
        return $this->find($data, ...$this->teams($data)) !== null;
    }

    public function save(array $data): bool
    {
        [$home, $away] = $this->teams($data);
        $game = $this->find($data, $home, $away) ?? new Game;
        $game->fill([
            'tournament_id' => $home->tournament_id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            ...collect($data)->only(['matchday', 'kickoff_at', 'venue', 'home_score', 'away_score', 'status']),
        ])->save();

        return $game->wasRecentlyCreated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{Team, Team}
     */
    private function teams(array $data): array
    {
        $base = ['torneo' => $data['torneo'], 'stagione' => $data['stagione']];

        return [
            $this->existingTeam($base + ['squadra' => $data['casa']]),
            $this->existingTeam($base + ['squadra' => $data['trasferta']]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function find(array $data, Team $home, Team $away): ?Game
    {
        return Game::where('home_team_id', $home->id)
            ->where('away_team_id', $away->id)
            ->where('matchday', $data['matchday'])
            ->first();
    }
}
