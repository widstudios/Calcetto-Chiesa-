<?php

namespace App\Http\Controllers;

use App\Enums\DrillCategory;
use App\Models\Drill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Libreria allenamenti: l'allenatore sceglie il tipo di esercizio e vede come si fa, con schema e video.
 * Solo l'admin aggiunge, modifica o toglie esercizi.
 */
class DrillController extends Controller
{
    public function index(Request $request): View
    {
        $category = DrillCategory::tryFrom((string) $request->query('categoria'));
        $drill = $request->filled('esercizio') ? Drill::find((int) $request->query('esercizio')) : null;
        // Un esercizio scelto porta con sé il suo tipo, così le due tendine restano coerenti.
        $category = $drill?->category ?? $category;

        $all = Drill::orderBy('name')->get();

        return view('drills.index', [
            'categories' => Drill::grouped($all)->map(fn ($drills, $value) => (object) [
                'category' => DrillCategory::from($value),
                'count' => $drills->count(),
            ]),
            'category' => $category,
            'options' => Drill::grouped($category ? $all->where('category', $category) : $all),
            'drill' => $drill,
            'total' => $all->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('drills.form', ['drill' => new Drill([
            'category' => DrillCategory::tryFrom((string) $request->query('categoria')) ?? DrillCategory::Riscaldamento,
            'age_min' => 6,
            'age_max' => 16,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $drill = Drill::create($this->validated($request));

        return redirect()->route('drills.index', ['esercizio' => $drill->id])->with('status', 'Esercizio aggiunto.');
    }

    public function edit(Drill $drill): View
    {
        return view('drills.form', ['drill' => $drill]);
    }

    public function update(Request $request, Drill $drill): RedirectResponse
    {
        $drill->update($this->validated($request));

        return redirect()->route('drills.index', ['esercizio' => $drill->id])->with('status', 'Esercizio aggiornato.');
    }

    public function destroy(Drill $drill): RedirectResponse
    {
        $category = $drill->category;
        $drill->delete();

        return redirect()->route('drills.index', ['categoria' => $category->value])->with('status', 'Esercizio eliminato.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(DrillCategory::class)],
            'age_min' => ['required', 'integer', 'between:4,18'],
            'age_max' => ['required', 'integer', 'between:4,18', 'gte:age_min'],
            'minutes' => ['nullable', 'integer', 'between:1,120'],
            'players' => ['nullable', 'string', 'max:60'],
            'equipment' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'setup' => ['nullable', 'string', 'max:2000'],
            'steps' => ['nullable', 'string', 'max:4000'],
            'tips' => ['nullable', 'string', 'max:2000'],
            'diagram' => ['nullable', Rule::in(array_keys(Drill::DIAGRAMS))],
            'video_url' => ['nullable', 'url', 'max:255', 'regex:'.Drill::YOUTUBE_PATTERN],
            'video_title' => ['nullable', 'string', 'max:200'],
            'video_channel' => ['nullable', 'string', 'max:120'],
        ], [
            'name.required' => 'Scrivi il nome dell\'esercizio.',
            'description.required' => 'Scrivi a cosa serve l\'esercizio.',
            'age_max.gte' => 'L\'età massima non può essere minore di quella minima.',
            'video_url.url' => 'Incolla il link completo del video (https://...).',
            'video_url.regex' => 'Il video deve essere un link di YouTube (youtube.com o youtu.be).',
        ]);
    }
}
