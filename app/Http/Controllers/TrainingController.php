<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function store(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $team->trainings()->create($request->validate([
            'weekday' => ['required', 'integer', Rule::in(array_keys(Training::WEEKDAYS))],
            'field' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ], [
            'ends_at.after' => "L'orario di fine deve venire dopo quello di inizio.",
        ]));

        return redirect()->route('teams.show', $team)->with('status', 'Allenamento aggiunto.');
    }

    public function destroy(Team $team, Training $training): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $training->delete();

        return redirect()->route('teams.show', $team)->with('status', 'Allenamento tolto.');
    }
}
