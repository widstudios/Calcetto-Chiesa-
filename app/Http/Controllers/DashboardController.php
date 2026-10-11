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

        return view('dashboard.coach', [
            'teams' => $user->teams()
                ->with(['tournament', 'players' => fn ($q) => $q->orderBy('shirt_number')])
                ->get(),
        ]);
    }
}
