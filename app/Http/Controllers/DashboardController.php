<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard.admin', [
                'tournaments' => Tournament::withCount(['teams', 'games'])->latest()->get(),
                'upcomingGames' => Game::with(['homeTeam', 'awayTeam'])
                    ->where('kickoff_at', '>=', now())
                    ->orderBy('kickoff_at')
                    ->limit(5)
                    ->get(),
            ]);
        }

        if ($user->isReferee()) {
            // Solo le partite che arbitra: in sola lettura, senza dati di contatto.
            $games = Game::with(['tournament', 'homeTeam', 'awayTeam', 'mvp', 'performances.player'])
                ->whereRelation('referee', 'user_id', $user->id)
                ->orderByRaw('kickoff_at is null')->orderBy('kickoff_at')->orderBy('matchday')
                ->get();

            return view('dashboard.referee', [
                'upcoming' => $games->reject->isPlayed(),
                'played' => $games->filter->isPlayed()->reverse(),
            ]);
        }

        return view('dashboard.coach', [
            'teams' => $user->teams()
                ->with(['tournament', 'coaches', 'players' => fn ($q) => $q->orderBy('shirt_number')])
                ->get(),
        ]);
    }
}
