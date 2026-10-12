{{-- Una classifica individuale (marcatori, voti, migliori in campo). --}}
@if ($players->isEmpty())
    <div class="empty"><p>{{ $empty }}</p></div>
@else
    <ol class="leaderboard">
        @foreach ($players as $player)
            <li @class(['top' => $loop->first])>
                <span class="rank">{{ $loop->iteration }}</span>
                <span class="who">
                    <strong>{{ $player->fullName() }}</strong>
                    <span class="muted small">{{ $player->team->name }}@isset($note) · {{ $note($player) }}@endisset</span>
                </span>
                <span class="value">{{ $value($player) }}@isset($unit) <small>{{ $unit instanceof Closure ? $unit($player) : $unit }}</small>@endisset</span>
            </li>
        @endforeach
    </ol>
@endif
