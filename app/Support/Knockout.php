<?php

namespace App\Support;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Fase finale a eliminazione diretta dopo i gironi: le prime di ogni girone passano il turno,
 * poi quarti, semifinali e finale si creano da soli man mano che si inseriscono i risultati.
 */
class Knockout
{
    /**
     * Squadre che passano il turno, già in ordine di testa di serie: prima tutte le prime
     * (girone A, B...), poi tutte le seconde, e così via.
     *
     * @return Collection<int, object{team: Team, group: string, position: int}>
     */
    public static function qualifiers(Tournament $tournament): Collection
    {
        $perGroup = $tournament->qualifiers_per_group ?? 0;
        $groups = (new TournamentStats($tournament))->groupStandings()->except('');

        $list = collect();
        for ($position = 1; $position <= $perGroup; $position++) {
            foreach ($groups as $group => $rows) {
                if ($row = $rows->get($position - 1)) {
                    $list->push((object) ['team' => $row->team, 'group' => $group, 'position' => $position]);
                }
            }
        }

        return $list;
    }

    /**
     * Crea il primo turno della fase finale. Se qualcosa non va spiega cosa con una RuntimeException.
     *
     * @return int partite create
     */
    public static function start(Tournament $tournament): int
    {
        if (! $tournament->hasGroups() || ! $tournament->qualifiers_per_group) {
            throw new RuntimeException('Nel torneo indica quante squadre per girone passano alla fase finale.');
        }
        if ($tournament->knockoutGames()->exists()) {
            throw new RuntimeException('La fase finale è già stata creata.');
        }
        $missing = $tournament->games()->whereNull('stage')->where('status', '!=', GameStatus::Giocata)->count();
        if ($missing) {
            throw new RuntimeException("Mancano ancora {$missing} partite dei gironi: la fase finale si crea quando sono tutte giocate.");
        }

        $seeds = self::qualifiers($tournament)->values();
        $stage = GameStage::forGames(intdiv($seeds->count(), 2));
        if ($seeds->count() % 2 || ! $stage) {
            throw new RuntimeException("Passano il turno {$seeds->count()} squadre: per un tabellone servono 2, 4, 8 o 16 squadre. Cambia quante passano per girone.");
        }

        // La 1ª testa di serie contro l'ultima, la 2ª contro la penultima...
        $count = $seeds->count();
        $pairs = [];
        for ($i = 0; $i < $count / 2; $i++) {
            $pairs[] = [$seeds[$i], $seeds[$count - 1 - $i]];
        }
        $pairs = self::avoidSameGroup($pairs);

        // Ordine del tabellone: le teste di serie più alte si incontrano il più tardi possibile.
        foreach (self::bracketOrder(count($pairs)) as $slot => $seed) {
            [$home, $away] = $pairs[$seed - 1];
            Game::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $home->team->id,
                'away_team_id' => $away->team->id,
                'stage' => $stage,
                'bracket_slot' => $slot + 1,
                'status' => GameStatus::Programmata,
            ]);
        }

        return count($pairs);
    }

    /**
     * Se un turno è finito, crea il successivo con le vincenti: 1ª contro 2ª partita, 3ª contro 4ª...
     * Se un risultato viene corretto, aggiorna le squadre del turno dopo finché non è giocato.
     * Restituisce il turno creato, oppure null se non c'era niente da creare.
     */
    public static function advance(Tournament $tournament): ?GameStage
    {
        $games = $tournament->knockoutGames()->get();
        if ($games->isEmpty()) {
            return null;
        }

        foreach (GameStage::cases() as $stage) {
            $round = $games->where('stage', $stage)->values();
            $next = $stage->next();
            if (! $next || $round->count() < 2) {
                continue;
            }
            $nextRound = $games->where('stage', $next)->keyBy('bracket_slot');

            foreach ($round->chunk(2)->values() as $index => $pair) {
                [$first, $second] = $pair->values()->all();
                $teams = ['home_team_id' => $first->winnerId(), 'away_team_id' => $second->winnerId()];
                $existing = $nextRound->get($index + 1);

                if ($existing) {
                    // Risultato corretto dopo: si aggiorna la partita successiva se non è ancora giocata.
                    if ($teams['home_team_id'] && $teams['away_team_id'] && ! $existing->isPlayed()) {
                        $existing->update($teams);
                    }
                } elseif ($round->every(fn (Game $g) => $g->winnerId() !== null)) {
                    Game::create($teams + [
                        'tournament_id' => $tournament->id,
                        'stage' => $next,
                        'bracket_slot' => $index + 1,
                        'status' => GameStatus::Programmata,
                    ]);
                    $created = $next;
                }
            }
        }

        return $created ?? null;
    }

    /**
     * Prova a non far rincontrare subito due squadre dello stesso girone scambiando gli avversari.
     *
     * @param  list<array{0: object, 1: object}>  $pairs
     * @return list<array{0: object, 1: object}>
     */
    private static function avoidSameGroup(array $pairs): array
    {
        foreach ($pairs as $i => [$home, $away]) {
            if ($home->group !== $away->group) {
                continue;
            }
            foreach ($pairs as $j => [$otherHome, $otherAway]) {
                if ($j !== $i && $otherAway->group !== $home->group && $away->group !== $otherHome->group) {
                    [$pairs[$i][1], $pairs[$j][1]] = [$otherAway, $away];
                    break;
                }
            }
        }

        return $pairs;
    }

    /**
     * Ordine classico del tabellone per n partite: con 4 partite 1, 4, 2, 3.
     *
     * @return list<int>
     */
    private static function bracketOrder(int $n): array
    {
        $order = [1];
        while (count($order) < $n) {
            $size = count($order) * 2;
            $order = array_merge(...array_map(fn ($seed) => [$seed, $size + 1 - $seed], $order));
        }

        return $order;
    }
}
