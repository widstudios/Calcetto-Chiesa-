<?php

namespace App\Support;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Performance;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Numeri del torneo come in un campionato vero: classifica, marcatori, pagelle, portieri e migliori in campo.
 */
class TournamentStats
{
    public function __construct(private Tournament $tournament) {}

    /**
     * Classifica: 3 punti a vittoria, 1 a pareggio. A parità contano scontro diretto, differenza reti,
     * gol fatti, meno gol subiti e infine il nome (al posto del sorteggio).
     *
     * @return Collection<int, object{team: Team, played: int, won: int, drawn: int, lost: int, goals_for: int, goals_against: int, goal_difference: int, points: int, form: list<string>}>
     */
    public function standings(): Collection
    {
        return $this->table($this->tournament->teams()->orderBy('name')->get());
    }

    /**
     * Una classifica per girone, in ordine di lettera. Contano solo le partite tra squadre dello stesso girone.
     * Le squadre non ancora assegnate finiscono sotto la chiave '' («Senza girone»).
     *
     * @return Collection<string, Collection<int, object>>
     */
    public function groupStandings(): Collection
    {
        $teams = $this->tournament->teams()->orderBy('name')->get();
        $groups = collect($this->tournament->groupNames())->mapWithKeys(fn ($g) => [$g => collect()]);

        foreach ($teams->groupBy(fn ($team) => $team->group_name ?? '') as $group => $members) {
            $groups[$group] = $members;
        }

        return $groups
            ->sortKeys()
            ->sortBy(fn ($members, $group) => $group === '' ? 1 : 0)
            ->reject(fn ($members, $group) => $group === '' && $members->isEmpty())
            ->map(fn ($members, $group) => $this->table($members, $group !== ''));
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  bool  $sameGroupOnly  conta solo le partite tra squadre dello stesso girone
     */
    private function table(Collection $teams, bool $sameGroupOnly = false): Collection
    {
        $rows = $teams->mapWithKeys(fn ($team) => [$team->id => (object) [
            'team' => $team, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0, 'form' => [],
        ]]);

        // La fase finale non conta in classifica.
        $played = $this->tournament->games()->where('status', GameStatus::Giocata)->whereNull('stage')->get();

        foreach ($played as $game) {
            if ($sameGroupOnly && ! ($rows->has($game->home_team_id) && $rows->has($game->away_team_id))) {
                continue;
            }
            foreach ([[$game->home_team_id, $game->home_score, $game->away_score], [$game->away_team_id, $game->away_score, $game->home_score]] as [$teamId, $for, $against]) {
                $row = $rows->get($teamId);
                if (! $row) {
                    continue;
                }
                $row->played++;
                $row->goals_for += $for;
                $row->goals_against += $against;
                if ($for > $against) {
                    $row->won++;
                    $row->points += 3;
                    $row->form[] = 'V';
                } elseif ($for === $against) {
                    $row->drawn++;
                    $row->points += 1;
                    $row->form[] = 'N';
                } else {
                    $row->lost++;
                    $row->form[] = 'P';
                }
            }
        }

        $rows->each(function ($row) {
            $row->goal_difference = $row->goals_for - $row->goals_against;
            $row->form = array_slice($row->form, -5);
        });

        // Scontro diretto: punti fatti solo nelle partite tra le squadre arrivate a pari punti.
        $headToHead = [];
        foreach ($rows->groupBy('points')->filter(fn ($tied) => $tied->count() > 1) as $tied) {
            $ids = $tied->pluck('team.id')->all();
            foreach ($played as $game) {
                if (! in_array($game->home_team_id, $ids, true) || ! in_array($game->away_team_id, $ids, true)) {
                    continue;
                }
                $result = $game->home_score <=> $game->away_score;
                $headToHead[$game->home_team_id] = ($headToHead[$game->home_team_id] ?? 0) + [-1 => 0, 0 => 1, 1 => 3][$result];
                $headToHead[$game->away_team_id] = ($headToHead[$game->away_team_id] ?? 0) + [-1 => 3, 0 => 1, 1 => 0][$result];
            }
        }
        $h2h = fn ($row) => $headToHead[$row->team->id] ?? 0;

        return $rows->values()
            ->sort(fn ($a, $b) => [$b->points, $h2h($b), $b->goal_difference, $b->goals_for, $a->goals_against, $a->team->name]
                <=> [$a->points, $h2h($a), $a->goal_difference, $a->goals_for, $b->goals_against, $b->team->name])
            ->values();
    }

    /**
     * Classifica marcatori.
     *
     * @return Collection<int, Player>
     */
    public function topScorers(int $limit = 10): Collection
    {
        return $this->players()
            ->withSum(['performances as goals' => $this->inTournament()], 'goals')
            ->whereHas('performances', fn ($q) => $this->inTournament()($q)->where('goals', '>', 0))
            ->orderByDesc('goals')->orderBy('last_name')
            ->limit($limit)->get();
    }

    /**
     * Media voto dei calciatori che hanno almeno un voto.
     *
     * @return Collection<int, Player>
     */
    public function bestRatings(int $limit = 10): Collection
    {
        return $this->players()
            ->withAvg(['performances as average_rating' => $this->inTournament()], 'rating')
            ->withCount(['performances as rated_games' => fn ($q) => $this->inTournament()($q)->whereNotNull('rating')])
            ->whereHas('performances', fn ($q) => $this->inTournament()($q)->whereNotNull('rating'))
            ->orderByDesc('average_rating')->orderByDesc('rated_games')
            ->limit($limit)->get();
    }

    /**
     * Quante volte ognuno è stato eletto migliore in campo.
     *
     * @return Collection<int, Player>
     */
    public function mvpLeaders(int $limit = 5): Collection
    {
        return $this->players()
            ->withCount(['mvpGames as mvp_count' => fn ($q) => $q->where('tournament_id', $this->tournament->id)])
            ->whereHas('mvpGames', fn ($q) => $q->where('tournament_id', $this->tournament->id))
            ->orderByDesc('mvp_count')->orderBy('last_name')
            ->limit($limit)->get();
    }

    /**
     * Miglior portiere: meno gol subiti a partita, contando solo le partite in cui è stato
     * segnato in porta nel tabellino. A parità vince chi ha giocato più partite.
     *
     * @return Collection<int, Player>
     */
    public function bestGoalkeepers(int $limit = 10): Collection
    {
        $performances = Performance::query()
            ->where('goalkeeper', true)
            ->whereIn('game_id', Game::select('id')->where('tournament_id', $this->tournament->id)->where('status', GameStatus::Giocata))
            ->with(['game', 'player.team'])
            ->get()
            ->filter(fn ($p) => $p->player);

        return $performances->groupBy('player_id')
            ->map(function ($rows) {
                $player = $rows->first()->player;
                $player->keeper_games = $rows->count();
                $player->goals_conceded = $rows->sum(fn ($p) => $p->player->team_id === $p->game->home_team_id ? $p->game->away_score : $p->game->home_score);
                $player->clean_sheets = $rows->filter(fn ($p) => ($p->player->team_id === $p->game->home_team_id ? $p->game->away_score : $p->game->home_score) === 0)->count();
                $player->average_conceded = $player->goals_conceded / $player->keeper_games;

                return $player;
            })
            ->sort(fn ($a, $b) => [$a->average_conceded, $b->keeper_games, $a->last_name] <=> [$b->average_conceded, $a->keeper_games, $b->last_name])
            ->take($limit)
            ->values();
    }

    /**
     * @return Builder<Player>
     */
    private function players()
    {
        return Player::query()
            ->with('team')
            ->whereHas('team', fn ($q) => $q->where('tournament_id', $this->tournament->id));
    }

    private function inTournament(): \Closure
    {
        return fn ($q) => $q->whereIn('game_id', Game::select('id')->where('tournament_id', $this->tournament->id)->where('status', GameStatus::Giocata));
    }
}
