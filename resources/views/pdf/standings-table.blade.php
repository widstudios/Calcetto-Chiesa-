{{-- Tabella di una classifica nel PDF (tutto il torneo o un girone). --}}
<table class="grid">
    <thead>
        <tr><th class="num">#</th><th>Squadra</th><th class="num">Pt</th><th class="num">G</th><th class="num">V</th><th class="num">N</th><th class="num">P</th><th class="num">GF</th><th class="num">GS</th><th class="num">DR</th></tr>
    </thead>
    <tbody>
        @forelse ($standings as $row)
            <tr>
                <td class="num">{{ $loop->iteration }}</td>
                <td>{{ $row->team->name }}</td>
                <td class="num"><strong>{{ $row->points }}</strong></td>
                <td class="num">{{ $row->played }}</td>
                <td class="num">{{ $row->won }}</td>
                <td class="num">{{ $row->drawn }}</td>
                <td class="num">{{ $row->lost }}</td>
                <td class="num">{{ $row->goals_for }}</td>
                <td class="num">{{ $row->goals_against }}</td>
                <td class="num">{{ $row->goal_difference > 0 ? '+' : '' }}{{ $row->goal_difference }}</td>
            </tr>
        @empty
            <tr><td colspan="10">Nessuna squadra.</td></tr>
        @endforelse
    </tbody>
</table>
