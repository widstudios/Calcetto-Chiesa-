<?php

namespace App\Support;

use App\Models\Setting;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Esporta una vista Blade in PDF con dompdf: PHP puro, funziona anche sull'hosting senza programmi esterni.
 */
class Pdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function download(string $view, array $data, string $filename, bool $landscape = false): Response
    {
        $options = new Options;
        // Niente risorse da internet: immagini e font si leggono solo dalla cartella public.
        $options->setIsRemoteEnabled(false);
        $options->setChroot(public_path());
        $options->setDefaultFont('DejaVu Sans');
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, [...$data, 'logoPath' => self::logoPath()])->render());
        $dompdf->setPaper('A4', $landscape ? 'landscape' : 'portrait');
        $dompdf->render();

        // Numero di pagina in basso a destra.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 28, 'Pagina {PAGE_NUM} di {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::slug(Str::beforeLast($filename, '.pdf')).'.pdf"',
        ]);
    }

    /**
     * Logo del sito come file locale, se dompdf lo sa leggere (PNG, GIF e WEBP gli servono l'estensione GD).
     */
    private static function logoPath(): ?string
    {
        $path = Setting::get('logo');
        $file = $path ? public_path('uploads/'.$path) : null;

        if (! $file || ! is_file($file)) {
            return null;
        }

        return extension_loaded('gd') || in_array(Str::lower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true) ? $file : null;
    }
}
