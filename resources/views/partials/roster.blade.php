{{-- Elenco calciatori di una squadra. $players, $team, $showRole, $editable --}}
<ul class="list">
    @foreach ($players as $player)
        @php($bench = $player->squad_role === \App\Enums\SquadRole::Panchina)
        <li class="list-item">
            <span @class(['shirt', 'bench' => $bench, 'none' => ! $player->shirt_number]) aria-label="Maglia numero {{ $player->shirt_number ?? 'non assegnato' }}">{{ $player->shirt_number ?? '–' }}</span>
            <div class="list-main">
                <div class="list-title">{{ $player->fullName() }}</div>
                <div class="list-meta">
                    <span>Nato nel {{ $player->birth_year }}</span>
                    @if ($showRole ?? false)
                        <span @class(['badge', 'bench' => $bench])>{{ $player->squad_role->label() }}</span>
                    @endif
                </div>
            </div>
            @if ($editable ?? false)
                <div class="actions">
                    <a class="btn small secondary" href="{{ route('teams.players.edit', [$team, $player]) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                    @include('partials.delete', ['action' => route('teams.players.destroy', [$team, $player]), 'confirm' => 'Togliere '.$player->fullName().' dalla rosa?', 'label' => 'Togli'])
                </div>
            @endif
        </li>
    @endforeach
</ul>
