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

        <label for="lineup_max_players">Giocatori in distinta al massimo</label>
        <input id="lineup_max_players" type="number" inputmode="numeric" min="1" max="40" name="lineup_max_players" value="{{ old('lineup_max_players', $lineupMaxPlayers) }}" style="max-width: 160px" @error('lineup_max_players') aria-invalid="true" aria-describedby="lineup_max_players-error" @enderror>
        <p class="hint">Il regolamento Oratorio Cup (art. 5.1) ne ammette fino a {{ App\Models\Setting::LINEUP_MAX_PLAYERS }}. Una distinta con più convocati non si salva. Se una categoria ha un limite diverso, mettilo nel suo torneo.</p>
        @include('partials.error', ['field' => 'lineup_max_players'])

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('dashboard') }}">Annulla</a>
        </div>
    </form>
@endsection
