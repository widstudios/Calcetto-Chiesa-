<?php

namespace App\Support;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Porta nel calendario le partite lette da un comunicato CSI (vedi CsiCommunique).
 *
 * - Ogni categoria del comunicato ("Under 10") va nel torneo con lo stesso nome; se manca, si salta.
 * - La nostra squadra si riconosce dal nome della società nei comunicati (impostazione "csi_club_name").
 * - Si importano le gare dei gironi in cui giochiamo noi; le squadre avversarie si creano se mancano.
 * - Una partita già presente (stesso codice gara, oppure stesse squadre e stesso giorno) viene aggiornata.
 */
class CsiImport
{
    /**
     * @param  array<string, array{code: string, category: ?string, group: ?string, home: string, away: string, kickoff: mixed, home_score: ?int, away_score: ?int, postponed?: bool}>  $games
     * @return array{found: int, saved: int, notes: list<string>}
     */
    public function apply(array $games): array
    {
        // Moduli e regolamenti: nessuna partita, niente da dire.
        if ($games === []) {
            return ['found' => 0, 'saved' => 0, 'notes' => []];
        }

        $club = Setting::get('csi_club_name');
        if (blank($club)) {
            return ['found' => count($games), 'saved' => 0, 'notes' => ['Scrivi il nome della società come appare nei comunicati, così riconosco le nostre partite.']];
        }
        $clubKey = CsiCommunique::key($club);

        $saved = 0;
        $missing = [];
        $byCategory = collect($games)->filter(fn ($g) => $g['category'])->groupBy('category');

        DB::transaction(function () use ($byCategory, $clubKey, &$saved, &$missing) {
            foreach ($byCategory as $category => $rows) {
                $ours = $rows->filter(fn ($g) => $this->isClub($g['home'], $clubKey) || $this->isClub($g['away'], $clubKey));
                $tournament = Tournament::query()->whereRaw('lower(name) = ?', [mb_strtolower($category)])->latest('id')->first();
                if (! $tournament) {
                    if ($ours->isNotEmpty()) {
                        $missing[] = $category;
                    }

                    continue;
                }

                $teams = $tournament->teams()->get();
                $groups = $ours->pluck('group')->filter()->unique();
                $known = $teams->map(fn ($t) => CsiCommunique::key($t->name));

                foreach ($rows as $game) {
                    $involvesUs = $this->isClub($game['home'], $clubKey) || $this->isClub($game['away'], $clubKey);
                    // Le gare del nostro girone, e quelle tra squadre che sono già nel torneo (comunicati precedenti).
                    $inGroup = $game['group'] && $groups->contains($game['group']);
                    $knownTeams = $known->contains(CsiCommunique::key($game['home'])) && $known->contains(CsiCommunique::key($game['away']));
                    if (! $involvesUs && ! $inGroup && ! $knownTeams) {
                        continue;
                    }

                    $home = $this->team($tournament, $teams, $game['home'], $clubKey);
                    $away = $this->team($tournament, $teams, $game['away'], $clubKey);
                    $known->push(CsiCommunique::key($home->name), CsiCommunique::key($away->name));

                    $saved += (int) $this->save($tournament, $home, $away, $game);
                }
            }
        });

        $notes = $missing ? ['Nel gestionale mancano queste categorie, le loro partite non sono state importate: '.implode(', ', array_unique($missing)).'.'] : [];

        return ['found' => count($games), 'saved' => $saved, 'notes' => $notes];
    }

    private function isClub(string $name, string $clubKey): bool
    {
        return str_contains(CsiCommunique::key($name), $clubKey);
    }

    /**
     * La squadra del torneo con quel nome. La nostra è quella con la rosa più numerosa,
     * oppure quella che si chiama come il torneo ("Under 10"). Se manca, si crea.
     */
    private function team(Tournament $tournament, Collection $teams, string $name, string $clubKey): Team
    {
        $key = CsiCommunique::key($name);
        $team = $teams->first(fn (Team $t) => CsiCommunique::key($t->name) === $key)
            // Il CSI a volte sbaglia una lettera ("Escrvà" e "Escrivà"): nomi lunghi quasi uguali sono la stessa squadra.
            ?? (strlen($key) >= 8 ? $teams->first(fn (Team $t) => strlen(CsiCommunique::key($t->name)) >= 8 && levenshtein(CsiCommunique::key($t->name), $key) <= 1) : null);

        if (! $team && $this->isClub($name, $clubKey)) {
            $team = $teams->filter(fn (Team $t) => $t->players()->exists())->sortByDesc(fn (Team $t) => $t->players()->count())->first()
                ?? $teams->first(fn (Team $t) => CsiCommunique::key($t->name) === CsiCommunique::key($tournament->name));
        }

        if (! $team) {
            $team = $tournament->teams()->create(['name' => $name]);
            $teams->push($team);
        }

        return $team;
    }

    /** @return bool true se la partita è stata creata o cambiata */
    private function save(Tournament $tournament, Team $home, Team $away, array $row): bool
    {
        $game = Game::query()->where('tournament_id', $tournament->id)->where('csi_code', $row['code'])
            ->where('home_team_id', $home->id)->where('away_team_id', $away->id)->first();

        // Partita inserita a mano prima del comunicato: stesse squadre, stesso giorno.
        if (! $game && $row['kickoff']) {
            $game = Game::query()->where('tournament_id', $tournament->id)->whereNull('csi_code')
                ->where('home_team_id', $home->id)->where('away_team_id', $away->id)
                ->whereDate('kickoff_at', $row['kickoff']->toDateString())->first();
        }

        $game ??= new Game(['tournament_id' => $tournament->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'status' => GameStatus::Programmata]);
        $game->csi_code = $row['code'];

        if ($row['kickoff']) {
            // Nei comunicati senza ora si tiene l'ora che c'era già.
            $kickoff = $row['kickoff'];
            if ($kickoff->format('H:i') === '00:00' && $game->kickoff_at) {
                $kickoff = $kickoff->copy()->setTimeFrom($game->kickoff_at);
            }
            $game->kickoff_at = $kickoff;
        }

        if ($row['home_score'] !== null) {
            $game->fill(['home_score' => $row['home_score'], 'away_score' => $row['away_score'], 'status' => GameStatus::Giocata]);
        } elseif (! empty($row['postponed'])) {
            $game->status = GameStatus::Rinviata;
        }

        if (! $game->isDirty()) {
            return false;
        }
        $game->save();

        return true;
    }
}
