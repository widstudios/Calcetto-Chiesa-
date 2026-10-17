@extends('layouts.app')

@section('title', 'Appello')

@section('content')
    @include('partials.form-head', [
        'back' => route('teams.attendances.show', $team),
        'eyebrow' => $team->name,
        'title' => 'Appello di '.$date->locale('it')->isoFormat('dddd D MMMM'),
    ])

    <form class="card form-card" method="POST" action="{{ route('teams.attendances.update', $team) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="data" value="{{ $date->toDateString() }}">
        <p class="hint" style="margin-top: 0">
            @foreach ($trainings as $training)
                <x-icon name="clock" /> Allenamento {{ $training->timeRange() }}, campo {{ $training->field }}@if (! $loop->last)<br>@endif
            @endforeach
        </p>
        <p class="hint">{{ $saved->isEmpty() ? 'Sono tutti segnati presenti: tocca "Assente" per chi manca.' : 'Appello già fatto: puoi correggerlo.' }}</p>
        @include('partials.error', ['field' => 'players'])

        @if ($team->players->isEmpty())
            <div class="empty"><x-icon name="shirt" /><p>Nessun calciatore in rosa.</p></div>
        @else
            <ul class="list roll-call" style="margin-top: 12px">
                @foreach ($team->players as $player)
                    @php($present = (string) old("players.{$player->id}", (int) ($saved->get($player->id)?->present ?? true)))
                    <li class="list-item">
                        <span @class(['shirt', 'bench' => $player->squad_role === App\Enums\SquadRole::Panchina, 'none' => ! $player->shirt_number]) aria-hidden="true">{{ $player->shirt_number ?? '–' }}</span>
                        <div class="list-main">
                            <div class="list-title" id="player-{{ $player->id }}">{{ $player->fullName() }}</div>
                        </div>
                        <div class="toggle" role="radiogroup" aria-labelledby="player-{{ $player->id }}">
                            <label class="present"><input type="radio" name="players[{{ $player->id }}]" value="1" @checked($present === '1')> Presente</label>
                            <label class="absent"><input type="radio" name="players[{{ $player->id }}]" value="0" @checked($present === '0')> Assente</label>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($drillOptions->isNotEmpty())
            <div class="field-group drill-picks">
                <span class="legend">Esercizi fatti in allenamento</span>
                <p class="hint" style="margin: -4px 0 8px">Scegli cosa avete fatto. Come si fa ogni esercizio lo trovi negli <a href="{{ route('drills.index') }}">Esercizi</a>.</p>
                @php($picked = collect(old('drills', $savedDrills->all()))->map(fn ($id) => (string) $id)->values())
                @for ($i = 0; $i < max(4, $picked->filter()->count() + 1); $i++)
                    <label class="sr-only" for="drill-{{ $i }}">Esercizio {{ $i + 1 }}</label>
                    <select id="drill-{{ $i }}" name="drills[]">
                        <option value="">Esercizio {{ $i + 1 }}: nessuno</option>
                        @foreach ($drillOptions as $drills)
                            <optgroup label="{{ $drills->first()->category->label() }}">
                                @foreach ($drills as $option)
                                    <option value="{{ $option->id }}" @selected($picked->get($i) === (string) $option->id)>{{ $option->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                @endfor
                @error('drills.*') <div class="error"><x-icon name="alert" /> {{ $message }}</div> @enderror
            </div>
        @endif

        <div class="form-actions">
            <button class="btn" type="submit"><x-icon name="check" /> Salva appello</button>
            <a class="btn secondary" href="{{ route('teams.attendances.show', $team) }}">Annulla</a>
        </div>
    </form>

    @if ($saved->isNotEmpty())
        <div class="actions" style="margin-top: 16px">
            <form method="POST" action="{{ route('teams.attendances.destroy', $team) }}" onsubmit="return confirm('Cancellare l\'appello del {{ $date->format('d/m/Y') }}?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="data" value="{{ $date->toDateString() }}">
                <button class="btn danger small" type="submit"><x-icon name="trash" /> Cancella appello</button>
            </form>
        </div>
    @endif
@endsection
