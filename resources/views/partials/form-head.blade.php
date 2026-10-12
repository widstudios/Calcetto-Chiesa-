<div class="page-head">
    <div>
        <a class="small" href="{{ $back }}" style="display: inline-flex; align-items: center; gap: 4px; min-height: 44px"><x-icon name="back" /> Indietro</a>
        @isset($eyebrow)<span class="eyebrow" style="display: block">{{ $eyebrow }}</span>@endisset
        <h1>{{ $title }}</h1>
    </div>
</div>
