<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Impostazioni del sito: nome, logo e colori, usati nel gestionale e nel sito pubblico.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'siteName' => Setting::get('site_name'),
            'logoUrl' => Setting::logoUrl(),
            'lineupMaxPlayers' => Setting::lineupMaxPlayers(),
            'colors' => Theme::colors(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Niente SVG: può contenere codice che il browser eseguirebbe.
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'lineup_max_players' => ['nullable', 'integer', 'min:1', 'max:40'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'preset' => ['nullable', 'in:'.implode(',', array_keys(Theme::PRESETS))],
        ], [
            'colors.*.regex' => 'Scegli il colore dalla tavolozza (formato #rrggbb).',
            'lineup_max_players.*' => 'Il massimo in distinta va da 1 a 40 giocatori.',
            'site_name.max' => 'Il nome può avere al massimo 60 caratteri.',
            'logo.mimes' => 'Il logo deve essere un\'immagine PNG, JPG, WEBP o GIF.',
            'logo.max' => 'Il logo può pesare al massimo 2 MB.',
            'logo.uploaded' => 'Il logo non è stato caricato: prova con un file più piccolo.',
        ]);

        // Combinazione pronta: arriva da un modulo a parte e cambia solo i colori.
        if (isset($data['preset'])) {
            self::saveColors(Theme::PRESETS[$data['preset']]['colors']);

            return redirect()->route('settings.edit')->with('status', 'Colori «'.Theme::PRESETS[$data['preset']]['label'].'» applicati.');
        }

        $colors = collect(Theme::COLORS)->mapWithKeys(fn ($c, $key) => [$key => strtolower($data['colors'][$key] ?? Theme::color($key))])->all();
        if ($errors = Theme::contrastErrors($colors)) {
            return back()->withInput()->withErrors(collect($errors)->mapWithKeys(fn ($m, $key) => ["colors.$key" => $m])->all());
        }

        Setting::put('site_name', trim($data['site_name'] ?? ''));
        self::saveColors($colors);
        // Vuoto o uguale al regolamento: non si salva, così vale il valore predefinito.
        $max = (int) ($data['lineup_max_players'] ?? Setting::LINEUP_MAX_PLAYERS);
        Setting::put('lineup_max_players', $max === Setting::LINEUP_MAX_PLAYERS ? null : (string) $max);

        $disk = Storage::disk('uploads');
        $oldLogo = Setting::get('logo');

        if ($request->hasFile('logo')) {
            // Nome sempre nuovo, così browser e telefoni non mostrano il logo vecchio dalla cache.
            $file = $request->file('logo');
            $path = $disk->putFileAs('', $file, 'logo-'.Str::lower(Str::random(10)).'.'.$file->extension());
            if (! $path) {
                // Cartella non scrivibile o disco pieno: il logo attuale resta com'è.
                return back()->withInput()->withErrors(['logo' => 'Non sono riuscito a salvare il logo sul server. Controlla i permessi della cartella public/uploads.']);
            }
            Setting::put('logo', $path);
        } elseif ($request->boolean('remove_logo')) {
            Setting::put('logo', null);
        }

        if ($oldLogo && $oldLogo !== Setting::get('logo')) {
            $disk->delete($oldLogo);
        }

        return redirect()->route('settings.edit')->with('status', 'Impostazioni salvate.');
    }

    /** @param  array<string, string>  $colors */
    private static function saveColors(array $colors): void
    {
        foreach ($colors as $key => $value) {
            // Uguale al predefinito: non si salva, così vale la grafica base.
            Setting::put('color_'.$key, $value === Theme::COLORS[$key][1] ? null : $value);
        }
    }
}
