<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Support\Knockout;
use App\Support\Pdf;
use App\Support\RoundRobin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $tournaments = Tournament::orderBy('name')->get();
        $tournament = $tournaments->firstWhere('id', $request->integer('tournament')) ?? $tournaments->first();

        return view('games.index', [
            'tournaments' => $tournaments,
            'tournament' => $tournament,
            'games' => $tournament
                ? $tournament->games()->with(['homeTeam', 'awayTeam', 'referee'])->orderByRaw('stage is not null')->orderBy('matchday')->orderBy('bracket_slot')->get()
                : collect(),
        ]);
    }

    public function pdf(Tournament $tournament): Response
    {
        return Pdf::download('pdf.games', [
            'tournament' => $tournament,
            'games' => $tournament->games()->with(['homeTeam', 'awayTeam'])->get(),
        ], 'calendario '.$tournament->name);
    }

    /**
     * Crea in automatico le partite all'italiana che mancano (in ogni girone, o tra tutte le squadre).
     */
    public function generate(Request $request, Tournament $tournament): RedirectResponse
    {
        $data = $request->validate([
            'first_day' => ['nullable', 'date'],
            'from' => ['nullable', 'required_with:first_day', 'date_format:H:i'],
            'to' => ['nullable', 'required_with:first_day', 'date_format:H:i', 'after:from'],
            'minutes' => ['nullable', 'required_with:first_day', 'integer', 'min:5', 'max:180'],
            'venue' => ['nullable', 'string', 'max:255'],
        ], [
            'to.after' => 'L\'orario di fine deve venire dopo quello di inizio.',
            '*.required_with' => 'Per dare gli orari servono giorno, inizio, fine e minuti a partita.',
        ]);
        $schedule = ! empty($data['first_day']) ? [
            'date' => $data['first_day'], 'from' => $data['from'], 'to' => $data['to'],
            'minutes' => (int) $data['minutes'], 'venue' => $data['venue'] ?? null,
        ] : null;

        $created = RoundRobin::generate($tournament, $request->boolean('return_leg'), $schedule);
        $back = redirect()->route('games.index', ['tournament' => $tournament->id]);

        if (! $created) {
            return $back->with('status', $tournament->hasGroups()
                ? 'Nessuna partita da creare: controlla di aver diviso le squadre nei gironi (Tornei → Modifica).'
                : 'Nessuna partita da creare: ci sono già tutte, oppure servono almeno due squadre.');
        }

        return $back->with('status', $schedule
            ? "Create {$created} partite con data e ora. Puoi sempre cambiarle una per una."
            : "Create {$created} partite. Ora puoi mettere data, ora e campo di ognuna.");
    }

    /**
     * Crea il primo turno della fase finale con le squadre che hanno passato i gironi.
     */
    public function knockout(Tournament $tournament): RedirectResponse
    {
        $back = redirect()->route('games.index', ['tournament' => $tournament->id]);

        try {
            $created = Knockout::start($tournament);
        } catch (RuntimeException $e) {
            return $back->withErrors(['knockout' => $e->getMessage()]);
        }

        return $back->with('status', "Fase finale creata: {$created} ".($created === 1 ? 'partita' : 'partite').'. I turni dopo si creano da soli quando inserisci i risultati.');
    }

    public function create(Request $request): View
    {
        $tournament = Tournament::findOrFail($request->integer('tournament'));

        return view('games.form', [
            'game' => new Game(['tournament_id' => $tournament->id, 'status' => GameStatus::Programmata]),
            'tournament' => $tournament,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tournament = Tournament::findOrFail($request->integer('tournament_id'));

        $tournament->games()->create($this->validated($request, $tournament));
        Knockout::advance($tournament);

        return redirect()->route('games.index', ['tournament' => $tournament->id])->with('status', 'Partita aggiunta al calendario.');
    }

    public function edit(Game $game): View
    {
        return view('games.form', ['game' => $game, 'tournament' => $game->tournament]);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $game->update($this->validated($request, $game->tournament, $game));
        Knockout::advance($game->tournament);

        return redirect()->route('games.index', ['tournament' => $game->tournament_id])->with('status', 'Partita aggiornata.');
    }

    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        return redirect()->route('games.index', ['tournament' => $game->tournament_id])->with('status', 'Partita eliminata.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Tournament $tournament, ?Game $game = null): array
    {
        $teamInTournament = Rule::exists(Team::class, 'id')->where('tournament_id', $tournament->id);

        $data = $request->validate([
            'home_team_id' => ['required', $teamInTournament],
            'away_team_id' => ['required', 'different:home_team_id', $teamInTournament],
            'referee_id' => ['nullable', 'integer', 'exists:referees,id'],
            'matchday' => ['nullable', 'integer', 'min:1', 'max:99'],
            'stage' => ['nullable', Rule::enum(GameStage::class)],
            'kickoff_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(GameStatus::class)],
            'home_score' => ['nullable', 'required_if:status,giocata', 'integer', 'min:0', 'max:99'],
            'away_score' => ['nullable', 'required_if:status,giocata', 'integer', 'min:0', 'max:99'],
            'home_penalties' => ['nullable', 'integer', 'min:0', 'max:99'],
            'away_penalties' => ['nullable', 'integer', 'min:0', 'max:99'],
        ], [
            'away_team_id.different' => 'Una squadra non può giocare contro sé stessa.',
            'home_score.required_if' => 'Inserisci il risultato della partita giocata.',
            'away_score.required_if' => 'Inserisci il risultato della partita giocata.',
        ]);

        // Il risultato ha senso solo per le partite giocate.
        if ($data['status'] !== GameStatus::Giocata->value) {
            $data['home_score'] = $data['away_score'] = null;
        }

        $data += ['stage' => null, 'home_score' => null, 'away_score' => null, 'home_penalties' => null, 'away_penalties' => null];

        // I rigori servono solo nella fase finale, quando i tempi regolamentari finiscono in parità.
        // Con andata e ritorno contano i gol delle due partite e i rigori si tirano al ritorno.
        $draw = $data['home_score'] !== null && (int) $data['home_score'] === (int) $data['away_score'];
        if ($data['stage'] && $data['stage'] === $game?->stage?->value && $game->leg) {
            $draw = $game->leg === 2 && $this->aggregateTied($game, $data);
        }
        if (! $data['stage'] || ! $draw) {
            $data['home_penalties'] = $data['away_penalties'] = null;
        } elseif ($data['home_penalties'] === null || $data['away_penalties'] === null || (int) $data['home_penalties'] === (int) $data['away_penalties']) {
            throw ValidationException::withMessages(['home_penalties' => $game?->leg === 2
                ? 'Con andata e ritorno i gol sono pari: inserisci il risultato dei rigori.'
                : 'Nella fase finale serve un vincitore: inserisci il risultato dei rigori.']);
        }

        // Una partita aggiunta a mano nella fase finale va in fondo al suo turno.
        if ($data['stage'] && $data['stage'] !== $game?->stage?->value) {
            $data['bracket_slot'] = ($tournament->games()->where('stage', $data['stage'])->max('bracket_slot') ?? 0) + 1;
        }
        if (! $data['stage']) {
            $data['bracket_slot'] = null;
        }

        return $data;
    }

    /**
     * Al ritorno: con il risultato inserito, i gol dell'andata più quelli del ritorno sono pari?
     *
     * @param  array<string, mixed>  $data
     */
    private function aggregateTied(Game $game, array $data): bool
    {
        $first = Game::where('tournament_id', $game->tournament_id)->where('stage', $game->stage)
            ->where('bracket_slot', $game->bracket_slot)->where('leg', 1)->first();
        if (! $first?->isPlayed() || $data['home_score'] === null) {
            return false;
        }

        $return = new Game([
            'home_team_id' => $data['home_team_id'], 'away_team_id' => $data['away_team_id'],
            'home_score' => (int) $data['home_score'], 'away_score' => (int) $data['away_score'], 'status' => GameStatus::Giocata,
        ]);
        $goals = Knockout::aggregate(collect([$first, $return]));

        return ($goals[$return->home_team_id] ?? 0) === ($goals[$return->away_team_id] ?? 0);
    }
}
