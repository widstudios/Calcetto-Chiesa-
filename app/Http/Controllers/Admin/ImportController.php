<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\Importer;
use App\Imports\RowError;
use App\Imports\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Caricamento da Excel dal pannello: si sceglie cosa importare, si carica il file,
 * si controlla l'anteprima e si conferma. Niente viene salvato prima della conferma.
 */
class ImportController extends Controller
{
    private const MAX_ROWS = 2000;

    public function index(): View
    {
        return view('imports.index', ['importers' => array_map(Importer::for(...), array_combine(array_keys(Importer::TYPES), array_keys(Importer::TYPES)))]);
    }

    /**
     * Modello Excel con le intestazioni giuste e due righe di esempio.
     */
    public function template(string $type): BinaryFileResponse
    {
        $importer = Importer::for($type) ?? abort(404);

        $path = tempnam(sys_get_temp_dir(), 'modello');
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValuesWithStyle($importer->headings(), (new Style)->withFontBold(true)));
        foreach ($importer->example() as $values) {
            $writer->addRow(Row::fromValues($values));
        }
        $writer->close();

        return response()->download($path, "modello-{$type}.xlsx")->deleteFileAfterSend();
    }

    /**
     * Legge il file e mostra cosa verrà salvato, oppure le righe da correggere.
     */
    public function preview(Request $request, string $type): View|RedirectResponse
    {
        $importer = Importer::for($type) ?? abort(404);
        $request->validate(
            ['file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv,txt']],
            ['file.extensions' => 'Carica un file Excel (.xlsx) o CSV.', 'file.required' => 'Scegli il file da caricare.'],
        );
        $file = $request->file('file');

        try {
            ['header' => $header, 'rows' => $rows] = SpreadsheetReader::read($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (Throwable) {
            return back()->withErrors(['file' => 'Non riesco a leggere il file: salvalo da Excel come .xlsx o .csv e riprova.']);
        }

        $missing = array_diff($importer->requiredColumns(), $header);
        if ($missing !== []) {
            return back()->withErrors(['file' => 'Nella prima riga mancano le colonne: '.implode(', ', $missing).'. Parti dal modello da scaricare.']);
        }
        if ($rows === []) {
            return back()->withErrors(['file' => 'Il file non ha righe da caricare sotto le intestazioni.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            return back()->withErrors(['file' => 'Il file ha più di '.self::MAX_ROWS.' righe: dividilo in più file.']);
        }

        $parsed = $errors = $preview = [];
        foreach ($rows as $line => $row) {
            try {
                $data = $importer->parse($row);
                $parsed[] = $data;
                $preview[$line] = ['summary' => $importer->summary($data), 'exists' => $importer->exists($data)];
            } catch (RowError $e) {
                $errors[$line] = ucfirst($e->getMessage());
            }
        }

        if ($errors === []) {
            $request->session()->put('import', ['type' => $type, 'rows' => $parsed]);
        }

        return view('imports.preview', [
            'type' => $type,
            'importer' => $importer,
            'fileName' => $file->getClientOriginalName(),
            'preview' => $preview,
            'rowErrors' => $errors,
        ]);
    }

    /**
     * Salva le righe viste nell'anteprima, tutte insieme: se una fallisce non si salva niente.
     */
    public function store(Request $request, string $type): RedirectResponse
    {
        $importer = Importer::for($type) ?? abort(404);
        $import = $request->session()->pull('import');
        if (($import['type'] ?? null) !== $type) {
            return redirect()->route('imports.index')->withErrors(['file' => 'L\'anteprima è scaduta: carica di nuovo il file.']);
        }

        try {
            $added = DB::transaction(fn () => collect($import['rows'])->filter(fn (array $data) => $importer->save($data))->count());
        } catch (RowError $e) {
            // Qualcosa è cambiato dopo l'anteprima (es. una squadra eliminata).
            return redirect()->route('imports.index')->withErrors(['file' => 'Import annullato: '.$e->getMessage().' Ricarica il file.']);
        }
        $updated = count($import['rows']) - $added;

        return redirect($importer->route())->with('status', "{$importer->label()} da Excel: {$added} aggiunti, {$updated} aggiornati.");
    }
}
