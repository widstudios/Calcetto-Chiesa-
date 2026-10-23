<?php

namespace App\Support;

use App\Models\CsiDocument;
use App\Models\Document;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Sincronizzazione con la cartella Google Drive pubblica dell'Oratorio Cup (CSI Roma):
 * scarica i file nuovi o modificati, così l'admin non deve caricarli a mano.
 *
 * Usa le Google Drive API v3 con una chiave API (GOOGLE_API_KEY): basta perché la cartella è
 * condivisa con "chiunque abbia il link".
 */
class CsiSync
{
    private const API = 'https://www.googleapis.com/drive/v3/files';

    /** I file si salvano sul disco privato: si aprono solo dal pannello. */
    public const DISK = 'local';

    public const DIR = 'csi';

    public static function configured(): bool
    {
        return filled(config('services.csi.google_key')) && filled(config('services.csi.drive_folder'));
    }

    /**
     * Controlla la cartella e scarica quello che manca.
     *
     * @return array{new: int, updated: int, total: int, games: int}
     */
    public function run(): array
    {
        if (! self::configured()) {
            throw new RuntimeException('Manca la chiave Google: aggiungi GOOGLE_API_KEY nel file .env del server.');
        }

        try {
            $summary = ['new' => 0, 'updated' => 0, 'total' => 0, 'games' => 0];
            $downloaded = [];

            foreach ($this->files() as $file) {
                // Cartelle e documenti Google (Documenti, Fogli) non si scaricano come file.
                if (str_starts_with($file['mimeType'] ?? '', 'application/vnd.google-apps.')) {
                    continue;
                }

                $summary['total']++;
                $document = CsiDocument::firstOrNew(['drive_id' => $file['id']]);
                $modified = isset($file['modifiedTime']) ? Carbon::parse($file['modifiedTime']) : null;

                // Già scaricato e uguale su Drive: si salta, a meno che la copia sul server sia sparita.
                $unchanged = $modified && $document->drive_modified_at?->timestamp === $modified->timestamp;
                if ($document->exists && $document->path && $unchanged && Storage::disk(self::DISK)->exists($document->path)) {
                    continue;
                }

                $summary[$document->exists ? 'updated' : 'new']++;
                $oldPath = $document->path;
                $path = self::DIR.'/'.$file['id'].'-'.Str::slug(pathinfo($file['name'], PATHINFO_FILENAME)).'.'.(Str::lower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'bin');

                if (! Storage::disk(self::DISK)->put($path, $this->download($file['id']))) {
                    // Disco pieno o cartella non scrivibile: non si segna come scaricato, così si riprova.
                    $message = 'Non sono riuscito a salvare «'.$file['name'].'» sul server. Controlla lo spazio e i permessi della cartella storage.';
                    Setting::put('csi_sync_error', $message);

                    throw new RuntimeException($message);
                }
                if ($oldPath && $oldPath !== $path) {
                    Storage::disk(self::DISK)->delete($oldPath);
                }

                $document->fill([
                    'name' => $file['name'],
                    'mime_type' => $file['mimeType'] ?? null,
                    'size' => isset($file['size']) ? (int) $file['size'] : null,
                    'drive_modified_at' => $modified,
                    'path' => $path,
                    'downloaded_at' => now(),
                ])->save();
                $downloaded[] = $document;
            }

            // Dal più vecchio al più nuovo, così vale l'ultimo risultato pubblicato.
            foreach (collect($downloaded)->sortBy('drive_modified_at') as $document) {
                $summary['games'] += $this->import($document);
                if (! $document->isCommunique()) {
                    $this->publish($document);
                }
            }

            Setting::put('csi_synced_at', now()->toIso8601String());
            Setting::put('csi_sync_error', null);

            return $summary;
        } catch (ConnectionException|RequestException $e) {
            $message = $this->explain($e);
            Setting::put('csi_sync_error', $message);

            throw new RuntimeException($message, previous: $e);
        }
    }

    /**
     * Legge le partite di un comunicato PDF e le porta nel calendario.
     *
     * @return int partite create o aggiornate
     */
    public function import(CsiDocument $document): int
    {
        if (! $document->isPdf() || ! $document->path) {
            return 0;
        }

        try {
            $result = (new CsiImport)->apply(CsiCommunique::parseFile(Storage::disk(self::DISK)->path($document->path)));
        } catch (Throwable $e) {
            // Un PDF che non si legge non deve fermare gli altri.
            report($e);
            $result = ['found' => 0, 'saved' => 0, 'notes' => $document->isCommunique() ? ['Non sono riuscito a leggere questo PDF.'] : []];
        }

        $document->update([
            'imported_at' => now(),
            'games_found' => $result['found'],
            'games_saved' => $result['saved'],
            'import_note' => implode(' ', $result['notes']) ?: null,
        ]);

        return $result['saved'];
    }

    /**
     * Moduli, adesioni, regolamenti: una copia va in Documenti, visibile a tutti gli allenatori.
     * Se l'admin l'ha tolta da Documenti, torna solo quando il CSI cambia il file.
     */
    private function publish(CsiDocument $file): void
    {
        $disk = Storage::disk(self::DISK);
        $path = Document::FOLDER.'/'.basename($file->path);
        if (! $disk->copy($file->path, $path)) {
            return;
        }

        $document = $file->document ?? new Document(['for_everyone' => true]);
        if ($document->path && $document->path !== $path) {
            $disk->delete($document->path);
        }
        $document->fill([
            'title' => pathinfo($file->name, PATHINFO_FILENAME),
            'description' => $document->description ?? 'Dalla cartella del CSI Roma.',
            'path' => $path,
            'original_name' => $file->name,
            'mime_type' => $file->mime_type,
            'size' => $disk->size($path),
        ])->save();

        $file->update(['document_id' => $document->id]);
    }

    /** @return list<array<string, mixed>> */
    private function files(): array
    {
        $files = [];
        $token = null;

        do {
            $response = Http::timeout(30)->get(self::API, array_filter([
                'q' => "'".config('services.csi.drive_folder')."' in parents and trashed = false",
                'fields' => 'nextPageToken, files(id, name, mimeType, size, modifiedTime)',
                'orderBy' => 'modifiedTime desc',
                'pageSize' => 200,
                'pageToken' => $token,
                'key' => config('services.csi.google_key'),
            ]))->throw()->json();

            array_push($files, ...($response['files'] ?? []));
            $token = $response['nextPageToken'] ?? null;
        } while ($token);

        return $files;
    }

    private function download(string $id): string
    {
        return Http::timeout(120)->get(self::API.'/'.$id, ['alt' => 'media', 'key' => config('services.csi.google_key')])->throw()->body();
    }

    private function explain(ConnectionException|RequestException $e): string
    {
        if ($e instanceof ConnectionException) {
            return 'Google Drive non risponde: riprova più tardi.';
        }

        $reason = $e->response->json('error.message') ?? $e->getMessage();

        return match ($e->response->status()) {
            400, 403 => 'Google ha rifiutato la chiave API: controlla GOOGLE_API_KEY e che "Google Drive API" sia abilitata. ('.$reason.')',
            404 => 'La cartella del CSI non si trova più: forse hanno cambiato link. Aggiorna CSI_DRIVE_FOLDER nel file .env.',
            default => 'Errore di Google Drive ('.$e->response->status().'): '.$reason,
        };
    }
}
