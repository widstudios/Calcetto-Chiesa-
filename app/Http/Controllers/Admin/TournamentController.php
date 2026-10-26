<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TournamentFormat;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(): View
    {
        return view('tournaments.index', [
            'tournaments' => Tournament::withCount(['teams', 'games'])->latest('starts_on')->get(),
        ]);
    }

    public function create(): View
    {
        return view('tournaments.form', ['tournament' => new Tournament]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tournament = Tournament::create($this->validated($request));

        if ($tournament->hasGroups()) {
            return redirect()->route('tournaments.edit', $tournament)->with('status', 'Torneo creato. Quando avrai inserito le squadre, qui potrai dividerle nei gironi.');
        }

        return redirect()->route('tournaments.index')->with('status', 'Torneo creato.');
    }

    public function edit(Tournament $tournament): View
    {
        return view('tournaments.form', ['tournament' => $tournament, 'teams' => $tournament->teams()->orderBy('name')->get()]);
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament->update($this->validated($request));
        $this->saveGroups($request, $tournament);

        // Appena diventato a gironi: si resta qui per dividere le squadre.
        if ($tournament->hasGroups() && ! $request->has('groups') && $tournament->teams()->exists()) {
            return redirect()->route('tournaments.edit', $tournament)->with('status', 'Torneo aggiornato. Ora dividi le squadre nei gironi.');
        }

        return redirect()->route('tournaments.index')->with('status', 'Torneo aggiornato.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        // Le squadre spariscono a cascata nel database: i loro stemmi vanno cancellati a mano.
        Storage::disk('uploads')->delete($tournament->teams()->whereNotNull('logo')->pluck('logo')->all());
        $tournament->delete();

        return redirect()->route('tournaments.index')->with('status', 'Torneo eliminato, con le sue squadre e partite.');
    }

    /**
     * Salva il girone di ogni squadra scelto nel modulo e toglie i gironi che non esistono più
     * (torneo tornato campionato o con meno gironi).
     */
    private function saveGroups(Request $request, Tournament $tournament): void
    {
        $names = $tournament->groupNames();

        foreach ($tournament->teams as $team) {
            $group = $request->has('groups') ? ($request->input('groups.'.$team->id) ?: null) : $team->group_name;
            $team->update(['group_name' => in_array($group, $names, true) ? $group : null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'season' => ['nullable', 'string', 'max:20'],
            'format' => ['nullable', Rule::enum(TournamentFormat::class)],
            'groups_count' => ['nullable', 'required_if:format,gironi', 'integer', 'min:1', 'max:'.Tournament::MAX_GROUPS],
            'qualifiers_per_group' => ['nullable', 'integer', 'min:1', 'max:8'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'lineup_max_players' => ['nullable', 'integer', 'min:1', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'groups' => ['array'],
            'groups.*' => ['nullable', 'string', 'size:1'],
        ], [
            'lineup_max_players.*' => 'Il massimo in distinta va da 1 a 40 giocatori.',
            'groups_count.*' => 'Indica quanti gironi, da 1 a '.Tournament::MAX_GROUPS.'.',
            'qualifiers_per_group.*' => 'Le squadre che passano per girone vanno da 1 a 8.',
        ]);

        $data['format'] ??= TournamentFormat::Campionato->value;

        // Il numero di gironi conta solo nei tornei a gironi.
        if ($data['format'] !== TournamentFormat::Gironi->value) {
            $data['groups_count'] = $data['qualifiers_per_group'] = null;
        }

        return Arr::except($data, 'groups');
    }
}
