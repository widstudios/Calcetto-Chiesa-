@extends('pdf.layout')

@section('title', 'Rosa '.$team->name)
@section('eyebrow', $team->tournament->name)
@section('subtitle', 'Allenatore: '.($team->coachDisplayName() ?? 'non indicato'))

@section('content')
    @foreach (App\Enums\SquadRole::cases() as $role)
        @php($players = $team->players->where('squad_role', $role))
        <h2 @if ($loop->first) style="margin-top: 0" @endif>{{ $role->label() }} ({{ $players->count() }})</h2>
        <table class="grid">
            <thead>
                <tr><th class="num">N°</th><th>Cognome e nome</th><th class="num">Anno</th><th>Cellulari</th></tr>
            </thead>
            <tbody>
                @forelse ($players as $player)
                    <tr>
                        <td class="num">{{ $player->shirt_number }}</td>
                        <td>{{ $player->last_name }} {{ $player->first_name }}</td>
                        <td class="num">{{ $player->birth_year }}</td>
                        <td>{{ implode(' · ', $player->phones()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nessun calciatore.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    @if ($team->trainings->isNotEmpty())
        <h2>Allenamenti</h2>
        <table class="grid">
            <thead><tr><th>Giorno</th><th>Orario</th><th>Campo</th></tr></thead>
            <tbody>
                @foreach ($team->trainings as $training)
                    <tr><td>{{ $training->weekdayName() }}</td><td>{{ $training->timeRange() }}</td><td>{{ $training->field }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
