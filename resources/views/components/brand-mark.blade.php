@props(['small' => false])
{{-- Logo del sito: quello caricato in Impostazioni, altrimenti il pallone. --}}
@php($logo = \App\Models\Setting::logoUrl())
@if ($logo)
    <span {{ $attributes->class(['brand-mark', 'brand-logo', 'small' => $small]) }}><img src="{{ $logo }}" alt=""></span>
@else
    <span {{ $attributes->class(['brand-mark', 'small' => $small]) }}><x-icon name="ball" /></span>
@endif
