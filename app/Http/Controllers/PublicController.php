<?php

namespace App\Http\Controllers;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Models\Tournament;
use App\Support\Pdf;
use App\Support\TournamentStats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Pagina pubblica del torneo, visibile a tutti senza login: classifica, risultati, marcatori e pagelle.
 */
class PublicController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tournaments = Tournament::orderByDesc('starts_on')->orderByDesc('id')->get();
        $tournament = $tournaments->firstWhere('id', $request->integer('torneo')) ?? $tournaments->first();

        if (! $tournament) {
            return view('public.home', ['tournament' => null, 'tournaments' => $tournaments]);
        }

        $games = $tournament->games()
            ->with(['homeTeam', 'awayTeam', 'mvp', 'performances' => fn ($q) => $q->where('goals', '>', 0)->with('player')])
            ->get();
        $played = $games->where('status', GameStatus::Giocata);
        $stats = new TournamentStats($tournament);
        $matchdays = $games->reject->isKnockout()->groupBy(fn ($g) => $g->matchday ?? 0)->sortKeys();
        // Fase finale: un gruppo per turno, dagli ottavi alla finale.
        $knockout = collect(GameStage::cases())
            ->mapWithKeys(fn ($stage) => [$stage->value => $games->where('stage', $stage)->sortBy('bracket_slot')->values()])
            ->filter->isNotEmpty();
        // Si apre la prima giornata ancora da giocare (o l'ultima, a torneo finito).
        $openMatchday = $matchdays->search(fn ($day) => $day->contains(fn ($g) => ! $g->isPlayed()));
        if ($openMatchday === false) {
            $openMatchday = $matchdays->keys()->last();
        }

        return view('public.home', [
            'tournaments' => $tournaments,
            'tournament' => $tournament,
            'standings' => $stats->standings(),
            'groupStandings' => $tournament->hasGroups() ? $stats->groupStandings() : null,
            'scorers' => $stats->topScorers(),
            'ratings' => $stats->bestRatings(),
            'mvps' => $stats->mvpLeaders(),
            'latestResults' => $played->sortByDesc(fn ($g) => [$g->isKnockout(), $g->matchday, $g->kickoff_at])->take(4),
            'nextGames' => $games->where('status', '!=', GameStatus::Giocata)->sortBy(fn ($g) => [$g->kickoff_at === null, $g->kickoff_at])->take(4),
            'matchdays' => $matchdays,
            'knockout' => $knockout,
            'openMatchday' => $openMatchday,
            'totals' => [
                'played' => $played->count(),
                'goals' => $played->sum(fn ($g) => $g->home_score + $g->away_score),
                'teams' => $tournament->teams()->count(),
            ],
        ]);
    }

    /**
     * Classifica, marcatori e voti medi in PDF.
     */
    public function standingsPdf(Tournament $tournament): Response
    {
        $stats = new TournamentStats($tournament);

        return Pdf::download('pdf.standings', [
            'tournament' => $tournament,
            'standings' => $stats->standings(),
            'groupStandings' => $tournament->hasGroups() ? $stats->groupStandings() : null,
            'scorers' => $stats->topScorers(),
            'ratings' => $stats->bestRatings(),
        ], 'classifica '.$tournament->name);
    }
}
