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
 * Fase finale a eliminazione diretta dopo i gironi (o il girone unico): le prime passano il turno,
 * poi quarti, semifinali e finale si creano da soli man mano che si inseriscono i risultati.
 * Ogni turno può essere a partita secca o con andata e ritorno.
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
        $stats = new TournamentStats($tournament);
        // Girone unico (campionato): una sola classifica.
        $groups = $tournament->hasGroups() ? $stats->groupStandings()->except('') : collect(['' => $stats->standings()]);

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
        if (! $tournament->qualifiers_per_group) {
            throw new RuntimeException('Nel torneo indica quante squadre passano alla fase finale.');
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
        $created = 0;
        foreach (self::bracketOrder(count($pairs)) as $slot => $seed) {
            [$home, $away] = $pairs[$seed - 1];
            $created += self::createTie($tournament, $stage, $slot + 1, $home->team->id, $away->team->id);
        }

        return $created;
    }

    /**
     * Se un turno è finito, crea il successivo con le vincenti: 1° contro 2° accoppiamento, 3° contro 4°...
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
            $next = $stage->next();
            // Un accoppiamento è una partita secca, oppure andata e ritorno con lo stesso posto nel tabellone.
            $ties = $games->where('stage', $stage)->groupBy('bracket_slot')->sortKeys()->values();
            if (! $next || $ties->count() < 2) {
                continue;
            }
            $nextTies = $games->where('stage', $next)->groupBy('bracket_slot');
            $winners = $ties->map(fn ($legs) => self::tieWinner($legs));

            for ($i = 0; $i + 1 < $ties->count(); $i += 2) {
                $slot = intdiv($i, 2) + 1;
                $teams = ['home_team_id' => $winners[$i], 'away_team_id' => $winners[$i + 1]];
                $existing = $nextTies->get($slot);

                if ($existing) {
                    // Risultato corretto dopo: si aggiornano le squadre del turno dopo, ma solo se nessuna
                    // delle sue partite è già giocata, così andata e ritorno restano tra le stesse squadre.
                    if ($teams['home_team_id'] && $teams['away_team_id'] && ! $existing->contains->isPlayed()) {
                        $existing->each->update($teams);
                    }
                } elseif ($winners->every(fn ($id) => $id !== null)) {
                    self::createTie($tournament, $next, $slot, $teams['home_team_id'], $teams['away_team_id']);
                    $created = $next;
                }
            }
        }

        return $created ?? null;
    }

    /**
     * Chi passa il turno: in partita secca chi vince (anche ai rigori); con andata e ritorno chi segna
     * di più nelle due partite, e a parità chi vince i rigori del ritorno. Null se non è ancora deciso.
     *
     * @param  Collection<int, Game>  $legs
     */
    public static function tieWinner(Collection $legs): ?int
    {
        $legs = self::legsInOrder($legs);
        if ($legs->count() < 2) {
            return $legs->first()?->winnerId();
        }
        if ($legs->contains(fn (Game $g) => ! $g->isPlayed())) {
            return null;
        }

        $return = $legs->last();
        $goals = self::aggregate($legs);
        $home = $goals[$return->home_team_id] ?? 0;
        $away = $goals[$return->away_team_id] ?? 0;
        if ($home === $away && $return->hasPenalties()) {
            [$home, $away] = [$return->home_penalties, $return->away_penalties];
        }

        return match ($home <=> $away) {
            1 => $return->home_team_id,
            -1 => $return->away_team_id,
            default => null,
        };
    }

    /**
     * Gol fatti da ogni squadra nelle partite giocate di un accoppiamento.
     *
     * @param  Collection<int, Game>  $legs
     * @return array<int, int>
     */
    public static function aggregate(Collection $legs): array
    {
        $goals = [];
        foreach ($legs->filter->isPlayed() as $game) {
            $goals[$game->home_team_id] = ($goals[$game->home_team_id] ?? 0) + $game->home_score;
            $goals[$game->away_team_id] = ($goals[$game->away_team_id] ?? 0) + $game->away_score;
        }

        return $goals;
    }

    /**
     * @param  Collection<int, Game>  $legs
     * @return Collection<int, Game>
     */
    private static function legsInOrder(Collection $legs): Collection
    {
        return $legs->sortBy(fn (Game $g) => $g->leg ?? 0)->values();
    }

    /**
     * Crea le partite di un accoppiamento: una, oppure andata e ritorno se il turno lo prevede.
     *
     * @return int partite create
     */
    private static function createTie(Tournament $tournament, GameStage $stage, int $slot, int $home, int $away): int
    {
        $legs = $tournament->isTwoLeg($stage) ? [1, 2] : [null];

        foreach ($legs as $leg) {
            Game::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $home,
                'away_team_id' => $away,
                'stage' => $stage,
                'leg' => $leg,
                'bracket_slot' => $slot,
                'status' => GameStatus::Programmata,
            ]);
        }

        return count($legs);
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
