@extends('layouts.app')

@section('title', 'Impostazioni')

@section('content')
    @include('partials.form-head', ['back' => route('dashboard'), 'eyebrow' => 'Sito e gestionale', 'title' => 'Impostazioni'])

    <form class="card form-card" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <label for="site_name" style="margin-top: 0">Nome del sito</label>
        <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $siteName) }}" maxlength="60" placeholder="{{ config('app.name') }}" @error('site_name') aria-invalid="true" aria-describedby="site_name-error" @enderror>
        <p class="hint">Compare in alto accanto al logo, nel sito pubblico e nel titolo delle pagine. Lascia vuoto per usare «{{ config('app.name') }}».</p>
        @include('partials.error', ['field' => 'site_name'])

        <label for="logo">Logo</label>
        <div class="logo-field">
            <div class="logo-preview">
                <x-brand-mark />
                <span class="muted small">{{ $logoUrl ? 'Logo attuale' : 'Ora c\'è il pallone predefinito' }}</span>
            </div>
            <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" @error('logo') aria-invalid="true" aria-describedby="logo-error" @enderror>
        </div>
        <p class="hint">PNG, JPG, WEBP o GIF fino a 2 MB. Meglio un'immagine quadrata o rotonda con lo sfondo trasparente: viene mostrata su un riquadro bianco. Lo stesso logo diventa l'icona della scheda del browser e compare sulle distinte stampate.</p>
        @include('partials.error', ['field' => 'logo'])

        @if ($logoUrl)
            <label class="inline-check"><input type="checkbox" name="remove_logo" value="1"> Togli il logo e torna al pallone</label>
        @endif

        <fieldset class="theme-fields">
            <legend>Colori dell'app</legend>
            <p class="hint" style="margin-top: 0">Valgono per il gestionale, la pagina di accesso e il sito pubblico. Una combinazione pronta si applica subito. Se scegli tu i colori, premi «Salva»: se un colore si legge male non viene salvato e ti dico quale cambiare.</p>

            <div class="theme-presets">
                <span class="muted small">Combinazioni pronte:</span>
                @foreach (App\Support\Theme::PRESETS as $key => $preset)
                    <button class="btn small secondary" type="submit" form="preset-form" name="preset" value="{{ $key }}">
                        <span class="preset-dots" aria-hidden="true">
                            @foreach (array_unique([$preset['colors']['header_bg'], $preset['colors']['primary'], $preset['colors']['accent']]) as $dot)
                                <i style="background: {{ $dot }}"></i>
                            @endforeach
                        </span>
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="theme-grid">
                @foreach (App\Support\Theme::COLORS as $key => [$label, $default])
                    <div class="color-field">
                        <label for="color_{{ $key }}">{{ $label }}</label>
                        <div class="color-input">
                            <input id="color_{{ $key }}" type="color" name="colors[{{ $key }}]" value="{{ old("colors.$key", $colors[$key]) }}" data-theme-color="{{ $key }}" @error("colors.$key") aria-invalid="true" aria-describedby="colors.{{ $key }}-error" @enderror>
                            <code>{{ old("colors.$key", $colors[$key]) }}</code>
                        </div>
                        @include('partials.error', ['field' => "colors.$key"])
                    </div>
                @endforeach
            </div>

            {{-- Anteprima: si aggiorna mentre scegli, i colori diventano effettivi con "Salva" --}}
            <div class="theme-preview" id="theme-preview" aria-label="Anteprima dei colori">
                <div class="theme-preview-bar">
                    <x-brand-mark small />
                    <strong>{{ App\Models\Setting::siteName() }}</strong>
                    <span class="theme-preview-pill">Calendario</span>
                    <span class="theme-preview-dim">Squadre</span>
                </div>
                <div class="theme-preview-body">
                    <span class="theme-preview-title">Titolo della pagina</span>
                    <p>Testo normale con <a href="#theme-preview" onclick="return false">un link</a>.</p>
                    <span class="theme-preview-btn">Salva</span>
                    <span class="theme-preview-num">12</span>
                </div>
            </div>
        </fieldset>

        <label for="lineup_max_players">Giocatori in distinta al massimo</label>
        <input id="lineup_max_players" type="number" inputmode="numeric" min="1" max="40" name="lineup_max_players" value="{{ old('lineup_max_players', $lineupMaxPlayers) }}" style="max-width: 160px" @error('lineup_max_players') aria-invalid="true" aria-describedby="lineup_max_players-error" @enderror>
        <p class="hint">Il regolamento Oratorio Cup (art. 5.1) ne ammette fino a {{ App\Models\Setting::LINEUP_MAX_PLAYERS }}. Una distinta con più convocati non si salva. Se una categoria ha un limite diverso, mettilo nel suo torneo.</p>
        @include('partials.error', ['field' => 'lineup_max_players'])

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('dashboard') }}">Annulla</a>
        </div>
    </form>

    {{-- Modulo a parte: i pulsanti delle combinazioni cambiano solo i colori, senza salvare il resto --}}
    <form id="preset-form" method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')
    </form>

    <script>
        // Anteprima dal vivo dei colori scelti, prima di salvare.
        (() => {
            const preview = document.getElementById('theme-preview');
            const vars = { header_bg: '--p-header-bg', header_text: '--p-header-text', primary: '--p-primary', heading: '--p-heading', accent: '--p-accent' };
            document.querySelectorAll('[data-theme-color]').forEach((input) => {
                const apply = () => {
                    preview.style.setProperty(vars[input.dataset.themeColor], input.value);
                    input.nextElementSibling.textContent = input.value;
                };
                input.addEventListener('input', apply);
                apply();
            });
        })();
    </script>
@endsection
