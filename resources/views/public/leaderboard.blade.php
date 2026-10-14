{{-- Una classifica individuale (marcatori, voti, migliori in campo). --}}
@if ($players->isEmpty())
    <div class="empty"><p>{{ $empty }}</p></div>
@else
    {{-- I primi 5 sempre visibili, gli altri dietro "Vedi tutti" per non allungare troppo la pagina sul telefono. --}}
    @foreach ($players->values()->partition(fn ($p, $i) => $i < 5) as $group)
        @continue($group->isEmpty())
        @if (! $loop->first)
            <details class="leaderboard-more">
                <summary>Vedi tutti ({{ $players->count() }})</summary>
        @endif
        <ol class="leaderboard" start="{{ $group->keys()->first() + 1 }}">
            @foreach ($group as $i => $player)
                <li @class(['top' => $i === 0])>
                    <span class="rank">{{ $i + 1 }}</span>
                    <span class="who">
                        <strong>{{ $player->fullName() }}</strong>
                        <span class="muted small">{{ $player->team->name }}@isset($note) · {{ $note($player) }}@endisset</span>
                    </span>
                    <span class="value">{{ $value($player) }}@isset($unit) <small>{{ $unit instanceof Closure ? $unit($player) : $unit }}</small>@endisset</span>
                </li>
            @endforeach
        </ol>
        @if (! $loop->first)
            </details>
        @endif
    @endforeach
@endif
