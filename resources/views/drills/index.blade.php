@extends('layouts.app')

@section('title', $drill ? $drill->name : 'Esercizi')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Libreria allenamenti</span>
            <h1>Esercizi</h1>
            <p class="muted">Scegli il tipo di allenamento e l'esercizio: sotto trovi come si fa, lo schema e un video di esempio.</p>
        </div>
        @if (auth()->user()->isAdmin())
            <a class="btn" href="{{ route('drills.create', ['categoria' => $category?->value]) }}"><x-icon name="plus" /> Nuovo esercizio</a>
        @endif
    </div>

    @if ($total === 0)
        <div class="card empty">
            <x-icon name="cone" />
            <p>La libreria è vuota. {{ auth()->user()->isAdmin() ? 'Carica gli esercizi pronti con "php artisan db:seed --class=DrillSeeder --force" o aggiungine uno.' : 'Chiedi all\'amministratore di caricare gli esercizi.' }}</p>
        </div>
    @else
        <form class="card drill-picker" method="GET" action="{{ route('drills.index') }}">
            <div class="grid">
                <div>
                    <label for="categoria" style="margin-top: 0">Tipo di allenamento</label>
                    <select id="categoria" name="categoria" onchange="this.form.esercizio.value = ''; this.form.submit()">
                        <option value="">Tutti i tipi</option>
                        @foreach ($categories as $row)
                            <option value="{{ $row->category->value }}" @selected($category === $row->category)>{{ $row->category->label() }} ({{ $row->count }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="esercizio" style="margin-top: 0">Esercizio</label>
                    <select id="esercizio" name="esercizio" onchange="this.form.submit()">
                        <option value="">Scegli l'esercizio</option>
                        @foreach ($options as $drills)
                            <optgroup label="{{ $drills->first()->category->label() }}">
                                @foreach ($drills as $option)
                                    <option value="{{ $option->id }}" @selected($drill?->is($option))>{{ $option->name }} ({{ $option->ageLabel() }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>
            <noscript><button class="btn" type="submit" style="margin-top: 12px">Mostra</button></noscript>
        </form>

        @if ($drill)
            <article class="card drill">
                <div class="card-head">
                    <div>
                        <span class="eyebrow">{{ $drill->category->label() }}</span>
                        <h2>{{ $drill->name }}</h2>
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('drills.edit', $drill) }}"><x-icon name="pencil" /> Modifica</a>
                            @include('partials.delete', ['action' => route('drills.destroy', $drill), 'confirm' => 'Eliminare l\'esercizio "'.$drill->name.'"?'])
                        </div>
                    @endif
                </div>

                <div class="list-meta drill-facts">
                    <span><x-icon name="users" /> {{ $drill->ageLabel() }}</span>
                    @if ($drill->minutes)<span><x-icon name="clock" /> {{ $drill->minutes }} minuti</span>@endif
                    @if ($drill->players)<span><x-icon name="shirt" /> {{ $drill->players }}</span>@endif
                    @if ($drill->equipment)<span><x-icon name="cone" /> {{ $drill->equipment }}</span>@endif
                </div>

                <p class="drill-lead">{{ $drill->description }}</p>

                <div class="drill-body">
                    <div class="drill-text">
                        @if ($drill->setup)
                            <h3>Come si prepara</h3>
                            <p>{{ $drill->setup }}</p>
                        @endif
                        @if ($drill->stepList()->isNotEmpty())
                            <h3>Cosa devono fare</h3>
                            <ol class="drill-steps">
                                @foreach ($drill->stepList() as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        @endif
                        @if ($drill->tipList()->isNotEmpty())
                            <h3>Consigli per il mister</h3>
                            <ul class="drill-tips">
                                @foreach ($drill->tipList() as $tip)
                                    <li>{{ $tip }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if ($drill->diagram && isset(App\Models\Drill::DIAGRAMS[$drill->diagram]))
                        <figure class="drill-diagram">
                            @include('drills.diagram', ['type' => $drill->diagram])
                            <figcaption>
                                Schema: {{ App\Models\Drill::DIAGRAMS[$drill->diagram] }}.
                                <span class="legend-key"><i class="k-run"></i> corsa</span>
                                <span class="legend-key"><i class="k-ball"></i> palla</span>
                            </figcaption>
                        </figure>
                    @endif
                </div>

                @if ($drill->embedUrl())
                    <h3>Video di esempio</h3>
                    <div class="video">
                        <iframe src="{{ $drill->embedUrl() }}" title="{{ $drill->video_title ?? 'Video: '.$drill->name }}" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                    <p class="hint">
                        @if ($drill->video_title)«{{ $drill->video_title }}»@endif
                        @if ($drill->video_channel) dal canale {{ $drill->video_channel }}.@endif
                        <a href="{{ $drill->watchUrl() }}" target="_blank" rel="noopener">Apri su YouTube</a>
                    </p>
                @endif
            </article>
        @else
            @foreach ($options as $value => $drills)
                <section class="card flush">
                    <div class="card-head">
                        <h2>{{ $drills->first()->category->label() }}</h2>
                        <span class="badge">{{ $drills->count() }}</span>
                    </div>
                    <ul class="list" style="margin-top: 8px">
                        @foreach ($drills as $option)
                            <li class="list-item">
                                <span class="avatar" aria-hidden="true"><x-icon :name="$option->youtubeId() ? 'play' : 'cone'" /></span>
                                <div class="list-main">
                                    <a class="list-title" href="{{ route('drills.index', ['esercizio' => $option->id]) }}">{{ $option->name }}</a>
                                    <div class="list-meta">
                                        <span>{{ $option->ageLabel() }}</span>
                                        @if ($option->minutes)<span><x-icon name="clock" /> {{ $option->minutes }} min</span>@endif
                                        @if ($option->youtubeId())<span><x-icon name="play" /> Video</span>@endif
                                    </div>
                                </div>
                                <div class="actions">
                                    <a class="btn small secondary" href="{{ route('drills.index', ['esercizio' => $option->id]) }}"><span class="btn-text">Vedi</span> <x-icon name="chevron" /></a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif

        <p class="hint">Altre idee per l'attività di base: <a href="https://figc.it/media/103560/programma-di-sviluppo-territoriale-attivita-di-base-eserciziario.pdf" target="_blank" rel="noopener">Eserciziario FIGC Settore Giovanile e Scolastico (PDF)</a>.</p>
    @endif
@endsection
