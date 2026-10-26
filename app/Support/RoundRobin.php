<?php

namespace App\Support;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Tournament;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendario all'italiana: ogni squadra incontra tutte le altre del suo girone (o del campionato).
 */
class RoundRobin
{
    /**
     * Abbinamenti giornata per giornata col metodo del cerchio: con un numero dispari di squadre
     * a turno una riposa.
     *
     * @param  list<int>  $teamIds
     * @return list<list<array{0: int, 1: int}>> per ogni giornata, le coppie [casa, ospite]
     */
    public static function rounds(array $teamIds): array
    {
        if (count($teamIds) < 2) {
            return [];
        }
        if (count($teamIds) % 2) {
            $teamIds[] = null;
        }

        $n = count($teamIds);
        $rounds = [];
        for ($round = 0; $round < $n - 1; $round++) {
            $pairs = [];
            for ($i = 0; $i < $n / 2; $i++) {
                [$home, $away] = [$teamIds[$i], $teamIds[$n - 1 - $i]];
                if ($home === null || $away === null) {
                    continue;
                }
                // Si alterna casa e trasferta, così nessuno gioca sempre in casa.
                $pairs[] = ($round + $i) % 2 ? [$away, $home] : [$home, $away];
            }
            $rounds[] = $pairs;
            // Il primo resta fermo, gli altri girano di un posto.
            $teamIds = [$teamIds[0], $teamIds[$n - 1], ...array_slice($teamIds, 1, $n - 2)];
        }

        return $rounds;
    }

    /**
     * Crea le partite che mancano nel torneo. Le coppie che hanno già una partita non vengono toccate.
     *
     * Con $schedule (giorno, dalle, alle, minuti, campo) dà anche data e ora: su un solo campo,
     * una partita dopo l'altra, saltando gli orari già presi da partite di qualunque torneo.
     * Finito l'orario si continua lo stesso giorno della settimana successiva.
     *
     * @param  array{date: CarbonInterface, from: string, to: string, minutes: int, venue?: ?string}|null  $schedule
     * @return int quante partite sono state create
     */
    public static function generate(Tournament $tournament, bool $returnLeg = false, ?array $schedule = null): int
    {
        $teams = $tournament->teams()->orderBy('name')->get();
        /** @var Collection<string, Collection> $pools */
        $pools = $tournament->hasGroups()
            ? $teams->whereNotNull('group_name')->groupBy('group_name')->sortKeys()
            : collect(['' => $teams]);

        $existing = $tournament->games()->get(['home_team_id', 'away_team_id'])
            ->map(fn ($g) => $g->home_team_id.'-'.$g->away_team_id)->flip();
        $new = [];

        foreach ($pools->values() as $poolIndex => $pool) {
            $rounds = self::rounds($pool->pluck('id')->all());
            $legs = $returnLeg
                ? [...$rounds, ...array_map(fn ($pairs) => array_map(fn ($p) => [$p[1], $p[0]], $pairs), $rounds)]
                : $rounds;

            foreach ($legs as $index => $pairs) {
                foreach ($pairs as [$home, $away]) {
                    // Sola andata: basta una partita tra le due, in qualunque campo.
                    $key = $home.'-'.$away;
                    if ($existing->has($key) || (! $returnLeg && $existing->has($away.'-'.$home))) {
                        continue;
                    }
                    $new[] = ['home_team_id' => $home, 'away_team_id' => $away, 'matchday' => $index + 1, 'pool' => $poolIndex];
                    $existing[$key] = true;
                }
            }
        }

        // Una giornata alla volta, alternando i gironi: così nessuno gioca due partite di fila.
        usort($new, fn ($a, $b) => [$a['matchday'], $a['pool']] <=> [$b['matchday'], $b['pool']]);
        $slots = $schedule ? self::slots($schedule, count($new)) : [];

        foreach ($new as $i => $game) {
            Game::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $game['home_team_id'],
                'away_team_id' => $game['away_team_id'],
                'matchday' => $game['matchday'],
                'kickoff_at' => $slots[$i] ?? null,
                'venue' => $schedule['venue'] ?? null,
                'status' => GameStatus::Programmata,
            ]);
        }

        return count($new);
    }

    /**
     * I primi $count orari liberi sul campo, a partire dal giorno indicato.
     *
     * @param  array{date: CarbonInterface, from: string, to: string, minutes: int}  $schedule
     * @return list<CarbonInterface>
     */
    private static function slots(array $schedule, int $count): array
    {
        $slots = [];
        $day = Carbon::parse($schedule['date'])->startOfDay();

        // Al massimo un anno di settimane: con orari sbagliati non si gira all'infinito.
        for ($week = 0; count($slots) < $count && $week < 52; $week++, $day = $day->copy()->addWeek()) {
            $time = $day->copy()->setTimeFromTimeString($schedule['from']);
            $end = $day->copy()->setTimeFromTimeString($schedule['to']);
            $taken = Game::whereBetween('kickoff_at', [$time, $end])->pluck('kickoff_at')
                ->map(fn ($t) => $t->format('Y-m-d H:i'))->flip();

            for (; $time->copy()->addMinutes($schedule['minutes'])->lte($end) && count($slots) < $count; $time = $time->copy()->addMinutes($schedule['minutes'])) {
                if (! $taken->has($time->format('Y-m-d H:i'))) {
                    $slots[] = $time;
                }
            }
        }

        return $slots;
    }
}
