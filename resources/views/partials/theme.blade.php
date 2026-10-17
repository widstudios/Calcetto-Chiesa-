{{-- Colori scelti in Impostazioni: sovrascrivono le variabili di app.css (che restano i predefiniti) --}}
<meta name="theme-color" content="{{ App\Support\Theme::color('header_bg') }}">
@if ($themeCss = App\Support\Theme::css())
    <style>{!! $themeCss !!}</style>
@endif
