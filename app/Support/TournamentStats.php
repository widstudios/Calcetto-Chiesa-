<?php

namespace App\Support;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Numeri del torneo come in un campionato vero: classifica, marcatori, pagelle e migliori in campo.
 */
class TournamentStats
{
    public function __construct(private Tournament $tournament) {}

    /**
     * Classifica: 3 punti a vittoria, 1 a pareggio. A parità contano differenza reti, gol fatti e nome.
     *
     * @return Collection<int, object{team: Team, played: int, won: int, drawn: int, lost: int, goals_for: int, goals_against: int, goal_difference: int, points: int, form: list<string>}>
     */
    public function standings(): Collection
    {
        $rows = $this->tournament->teams()->orderBy('name')->get()->mapWithKeys(fn ($team) => [$team->id => (object) [
            'team' => $team, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0, 'form' => [],
        ]]);

        $played = $this->tournament->games()->where('status', GameStatus::Giocata)->get();

        foreach ($played as $game) {
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

        return $rows->values()
            ->each(function ($row) {
                $row->goal_difference = $row->goals_for - $row->goals_against;
                $row->form = array_slice($row->form, -5);
            })
            ->sort(fn ($a, $b) => [$b->points, $b->goal_difference, $b->goals_for, $a->team->name]
                <=> [$a->points, $a->goal_difference, $a->goals_for, $b->team->name])
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
