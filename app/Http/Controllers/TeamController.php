<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $teams = Team::with(['tournament', 'coaches'])
            ->withCount('players')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereRelation('coaches', 'users.id', $user->id))
            ->when($request->integer('tournament'), fn ($q, $id) => $q->where('tournament_id', $id))
            ->orderBy('name')
            ->get();

        return view('teams.index', [
            'teams' => $teams,
            'tournaments' => $user->isAdmin() ? Tournament::orderBy('name')->get() : collect(),
        ]);
    }

    public function show(Team $team): View
    {
        Gate::authorize('view', $team);

        $team->load(['tournament', 'coaches', 'trainings', 'players' => fn ($q) => $q->orderBy('squad_role')->orderBy('shirt_number')]);

        return view('teams.show', ['team' => $team]);
    }

    public function pdf(Team $team): Response
    {
        Gate::authorize('view', $team);

        $team->load(['tournament', 'coaches', 'trainings', 'players' => fn ($q) => $q->orderBy('shirt_number')->orderBy('last_name')]);

        return Pdf::download('pdf.roster', ['team' => $team], 'rosa '.$team->name);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Team::class);

        return view('teams.form', [
            'team' => new Team(['tournament_id' => $request->integer('tournament') ?: null]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Team::class);

        $data = $this->validated($request);
        $team = Team::create(Arr::except($data, ['logo', 'remove_logo']));
        $team->coaches()->sync($data['coaches'] ?? []);
        if ($error = $this->saveLogo($request, $team)) {
            return redirect()->route('teams.edit', $team)->withErrors(['logo' => $error]);
        }

        return redirect()->route('teams.show', $team)->with('status', 'Squadra creata. Ora puoi inserire i calciatori.');
    }

    public function edit(Team $team): View
    {
        Gate::authorize('update', $team);

        return view('teams.form', ['team' => $team, ...$this->formOptions()]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $data = $this->validated($request, $team);
        $team->update(Arr::except($data, ['logo', 'remove_logo']));
        $team->coaches()->sync($data['coaches'] ?? []);
        if ($error = $this->saveLogo($request, $team)) {
            return back()->withInput()->withErrors(['logo' => $error]);
        }

        return redirect()->route('teams.show', $team)->with('status', 'Squadra aggiornata.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')->with('status', 'Squadra eliminata.');
    }

    /**
     * Salva il logo caricato (o lo toglie) e cancella quello vecchio. Restituisce un errore se non riesce.
     */
    private function saveLogo(Request $request, Team $team): ?string
    {
        $disk = Storage::disk('uploads');
        $oldLogo = $team->logo;

        if ($request->hasFile('logo')) {
            // Nome sempre nuovo, così browser e telefoni non mostrano il logo vecchio dalla cache.
            $file = $request->file('logo');
            $path = $disk->putFileAs('', $file, 'squadra-'.$team->id.'-'.Str::lower(Str::random(8)).'.'.$file->extension());
            if (! $path) {
                return 'Non sono riuscito a salvare il logo sul server. Controlla i permessi della cartella public/uploads.';
            }
            $team->update(['logo' => $path]);
        } elseif ($request->boolean('remove_logo')) {
            $team->update(['logo' => null]);
        }

        if ($oldLogo && $oldLogo !== $team->logo) {
            $disk->delete($oldLogo);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'tournaments' => Tournament::orderBy('name')->get(),
            'coaches' => User::where('role', Role::Allenatore)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Team $team = null): array
    {
        return $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('teams')->where('tournament_id', $request->input('tournament_id'))->ignore($team),
            ],
            'coach_name' => ['nullable', 'string', 'max:255'],
            'coaches' => ['array'],
            'coaches.*' => ['integer', Rule::exists('users', 'id')->where('role', Role::Allenatore->value)],
            'shirt_color' => ['nullable', Rule::in(array_keys(Team::SHIRT_COLORS))],
            'shirt_color_secondary' => ['nullable', Rule::in(array_keys(Team::SHIRT_COLORS))],
            // Niente SVG: può contenere codice che il browser eseguirebbe.
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'In questo torneo esiste già una squadra con questo nome.',
            'logo.mimes' => 'Il logo deve essere un\'immagine PNG, JPG, WEBP o GIF.',
            'logo.max' => 'Il logo può pesare al massimo 2 MB.',
            'logo.uploaded' => 'Il logo non è stato caricato: prova con un file più piccolo.',
        ]);
    }
}
