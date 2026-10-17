@props(['team', 'size' => null])
{{-- Stemma della squadra; senza stemma la maglia nei suoi colori, senza colori le iniziali. --}}
@php($colors = $team->shirtColors())
@if ($logo = $team->logoUrl())
    <span {{ $attributes->class(['team-badge', 'has-logo', $size]) }}><img src="{{ $logo }}" alt="" loading="lazy"></span>
@elseif ($colors)
    <span {{ $attributes->class(['team-badge', 'kit', $size]) }} title="Maglia {{ mb_strtolower($team->shirtColorsLabel()) }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="{{ $colors[0] }}" stroke="{{ $colors[1] ?? ($colors[0] === '#ffffff' ? '#94a3b8' : $colors[0]) }}" stroke-width="1.6" stroke-linejoin="round" d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>
    </span>
@else
    <span {{ $attributes->class(['team-badge', 'initials', $size]) }} aria-hidden="true">{{ mb_substr($team->name, 0, 2) }}</span>
@endif
