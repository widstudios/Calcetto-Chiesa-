<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TournamentFormat;
use App\Enums\TournamentRules;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        if ($error = $this->saveRulesPdf($request, $tournament)) {
            return redirect()->route('tournaments.edit', $tournament)->withErrors(['rules_pdf' => $error]);
        }

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
        $tournament->update($this->validated($request, $tournament));
        if ($error = $this->saveRulesPdf($request, $tournament)) {
            return back()->withInput()->withErrors(['rules_pdf' => $error]);
        }
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
     * Salva il PDF del regolamento proprio (o lo toglie) e cancella quello vecchio. Restituisce un errore se non riesce.
     */
    private function saveRulesPdf(Request $request, Tournament $tournament): ?string
    {
        $disk = Storage::disk('uploads');
        $old = $tournament->rules_pdf;

        if ($request->hasFile('rules_pdf')) {
            // Nome sempre nuovo, così nessuno scarica dalla cache il regolamento vecchio.
            $name = 'regolamento-'.$tournament->id.'-'.Str::lower(Str::random(8)).'.pdf';
            if (! $disk->putFileAs('', $request->file('rules_pdf'), $name)) {
                // Senza PDF e senza testo il regolamento proprio sarebbe vuoto: si torna a quello CSI.
                if ($tournament->hasOwnRules() && blank($tournament->rules_text) && ! $tournament->rules_pdf) {
                    $tournament->update(['rules_type' => TournamentRules::Csi]);
                }

                return 'Non sono riuscito a salvare il PDF sul server. Controlla i permessi della cartella public/uploads.';
            }
            $tournament->update(['rules_pdf' => $name]);
        } elseif ($request->boolean('remove_rules_pdf')) {
            $tournament->update(['rules_pdf' => null]);
        }

        if ($old && $old !== $tournament->rules_pdf) {
            $disk->delete($old);
        }

        return null;
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
    private function validated(Request $request, ?Tournament $tournament = null): array
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
            'rules_type' => ['nullable', Rule::enum(TournamentRules::class)],
            'rules_text' => ['nullable', 'string', 'max:20000'],
            'rules_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_rules_pdf' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'groups' => ['array'],
            'groups.*' => ['nullable', 'string', 'size:1'],
        ], [
            'lineup_max_players.*' => 'Il massimo in distinta va da 1 a 40 giocatori.',
            'groups_count.*' => 'Indica quanti gironi, da 1 a '.Tournament::MAX_GROUPS.'.',
            'qualifiers_per_group.*' => 'Le squadre che passano per girone vanno da 1 a 8.',
            'rules_text.max' => 'Il testo del regolamento è troppo lungo: carica piuttosto il PDF.',
            'rules_pdf.mimes' => 'Il regolamento deve essere un file PDF.',
            'rules_pdf.max' => 'Il PDF del regolamento può pesare al massimo 10 MB.',
            'rules_pdf.uploaded' => 'Il PDF non è stato caricato: prova con un file più piccolo.',
        ]);

        $data['rules_type'] ??= TournamentRules::Csi->value;

        // Un regolamento proprio deve avere almeno il testo o il PDF.
        $keepsPdf = $tournament?->rules_pdf && ! $request->boolean('remove_rules_pdf');
        if ($data['rules_type'] === TournamentRules::Proprio->value && blank($data['rules_text'] ?? null) && ! $request->hasFile('rules_pdf') && ! $keepsPdf) {
            throw ValidationException::withMessages(['rules_text' => 'Scrivi le regole del torneo oppure carica il PDF del regolamento.']);
        }

        $data['format'] ??= TournamentFormat::Campionato->value;

        // Il numero di gironi conta solo nei tornei a gironi.
        if ($data['format'] !== TournamentFormat::Gironi->value) {
            $data['groups_count'] = $data['qualifiers_per_group'] = null;
        }

        return Arr::except($data, ['groups', 'rules_pdf', 'remove_rules_pdf']);
    }
}
