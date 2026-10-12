@extends('layouts.app')

@section('title', 'Allenatori')

@section('content')
    <div class="toolbar">
        <h1>Allenatori</h1>
        <a class="btn small" href="{{ route('coaches.create') }}">+ Nuovo allenatore</a>
    </div>
    <p class="muted">Qui crei gli account con cui gli allenatori accedono per gestire la rosa della loro squadra. Se un allenatore non deve accedere, basta scrivere il suo nome nella scheda della squadra.</p>

    <div class="card table-wrap">
        @if ($coaches->isEmpty())
            <p class="muted">Nessun account allenatore.</p>
        @else
            <table>
                <thead><tr><th>Nome</th><th>Email</th><th>Squadre</th><th></th></tr></thead>
                <tbody>
                @foreach ($coaches as $coach)
                    <tr>
                        <td><strong>{{ $coach->name }}</strong></td>
                        <td>{{ $coach->email }}</td>
                        <td>{{ $coach->teams->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="actions">
                            <a class="btn small secondary" href="{{ route('coaches.edit', $coach) }}">Modifica</a>
                            @include('partials.delete', ['action' => route('coaches.destroy', $coach), 'confirm' => 'Eliminare l\'account di '.$coach->name.'?'])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
