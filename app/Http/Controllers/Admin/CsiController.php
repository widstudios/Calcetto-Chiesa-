<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CsiDocument;
use App\Models\Setting;
use App\Support\CsiSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comunicati CSI: elenco dei file presi dalla cartella Drive del CSI Roma e pulsante "Sincronizza ora".
 */
class CsiController extends Controller
{
    public function index(): View
    {
        $syncedAt = Setting::get('csi_synced_at');

        return view('csi.index', [
            'documents' => CsiDocument::query()->orderByDesc('drive_modified_at')->orderByDesc('id')->get(),
            'configured' => CsiSync::configured(),
            'syncedAt' => $syncedAt ? Carbon::parse($syncedAt)->timezone(config('app.timezone')) : null,
            'error' => Setting::get('csi_sync_error'),
            'folderUrl' => 'https://drive.google.com/drive/folders/'.config('services.csi.drive_folder'),
        ]);
    }

    public function sync(CsiSync $sync): RedirectResponse
    {
        try {
            $summary = $sync->run();
        } catch (RuntimeException $e) {
            return redirect()->route('csi.index')->withErrors(['sync' => $e->getMessage()]);
        }

        $status = match (true) {
            $summary['new'] + $summary['updated'] === 0 => 'Nessun comunicato nuovo.',
            default => collect([
                $summary['new'] ? $summary['new'].' '.($summary['new'] === 1 ? 'file nuovo' : 'file nuovi') : null,
                $summary['updated'] ? $summary['updated'].' '.($summary['updated'] === 1 ? 'aggiornato' : 'aggiornati') : null,
            ])->filter()->join(' e ').' scaricati dal CSI.',
        };

        return redirect()->route('csi.index')->with('status', $status);
    }

    public function show(CsiDocument $document): StreamedResponse
    {
        $disk = Storage::disk(CsiSync::DISK);
        abort_unless($document->path && $disk->exists($document->path), 404);

        // I PDF si aprono nel browser; gli altri file si scaricano.
        return $document->isPdf()
            ? $disk->response($document->path, $document->name, ['Content-Type' => 'application/pdf'])
            : $disk->download($document->path, $document->name);
    }
}
