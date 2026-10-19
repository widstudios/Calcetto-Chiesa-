<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documenti: un allenatore (o l'admin) allega un file e sceglie chi lo vede, tutti o solo alcune persone.
 * I file stanno sul disco privato e si scaricano solo da qui, dopo il controllo dei permessi.
 */
class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        return view('documents.index', [
            'documents' => Document::visibleTo($request->user())
                ->with(['author', 'viewers'])
                ->latest()
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Document::class);

        return view('documents.form', [
            'document' => new Document(['for_everyone' => true]),
            'people' => $this->people($request->user()),
            'chosen' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Document::class);

        $data = $this->validated($request, true);
        $file = $request->file('file');

        $document = Document::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'for_everyone' => $data['for_everyone'],
            'user_id' => $request->user()->id,
            'path' => $file->store(Document::FOLDER, Document::DISK),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
        $document->viewers()->sync($data['for_everyone'] ? [] : $data['viewers']);

        return redirect()->route('documents.index')->with('status', 'Documento caricato.');
    }

    public function edit(Request $request, Document $document): View
    {
        Gate::authorize('update', $document);

        return view('documents.form', [
            'document' => $document,
            'people' => $this->people($request->user(), $document),
            'chosen' => $document->viewers->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $data = $this->validated($request, false);

        $document->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'for_everyone' => $data['for_everyone'],
        ]);

        // Un nuovo file prende il posto del vecchio.
        if ($file = $request->file('file')) {
            $old = $document->path;
            $document->fill([
                'path' => $file->store(Document::FOLDER, Document::DISK),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
            Storage::disk(Document::DISK)->delete($old);
        }

        $document->save();
        $document->viewers()->sync($data['for_everyone'] ? [] : $data['viewers']);

        return redirect()->route('documents.index')->with('status', 'Documento aggiornato.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('status', 'Documento eliminato.');
    }

    /**
     * Apre il documento nel browser (PDF e immagini) o lo scarica.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        $disk = Storage::disk(Document::DISK);
        abort_unless($disk->exists($document->path), 404);

        $inline = ! $request->boolean('scarica') && in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);

        return $inline
            ? $disk->response($document->path, $document->original_name, ['Content-Type' => $document->mime_type])
            : $disk->download($document->path, $document->original_name);
    }

    /**
     * Persone tra cui scegliere: tutti gli utenti dell'app tranne chi carica (lo vede comunque).
     *
     * @return Collection<int, User>
     */
    private function people(User $user, ?Document $document = null): Collection
    {
        return User::whereKeyNot($document?->user_id ?? $user->id)->orderBy('name')->get();
    }

    /**
     * @return array{title: string, description?: ?string, for_everyone: bool, viewers: array<int, int>}
     */
    private function validated(Request $request, bool $fileRequired): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,csv,jpg,jpeg,png,webp,gif,zip'],
            'visibility' => ['required', Rule::in(['tutti', 'scelta'])],
            'viewers' => ['required_if:visibility,scelta', 'array'],
            'viewers.*' => ['integer', Rule::exists('users', 'id')],
        ], [
            'title.required' => 'Scrivi il titolo del documento.',
            'file.required' => 'Scegli il file da allegare.',
            'file.max' => 'Il file è troppo grande: massimo 20 MB.',
            'file.mimes' => 'Puoi allegare PDF, documenti Word, Excel, PowerPoint, immagini, testo o ZIP.',
            'file.uploaded' => 'Il file non è stato caricato: forse è troppo grande.',
            'viewers.required_if' => 'Scegli almeno una persona che può vedere il documento.',
        ]);

        $data['for_everyone'] = $data['visibility'] === 'tutti';
        $data['viewers'] = array_values(array_map('intval', $data['viewers'] ?? []));

        return $data;
    }
}
