{{-- Tabella di una classifica (tutto il torneo o un girone). --}}
<div class="table-wrap">
    <table class="standings">
        <thead>
            <tr>
                <th class="pos">#</th><th>Squadra</th><th class="num" title="Punti">Pt</th>
                <th class="num" title="Giocate">G</th><th class="num" title="Vinte">V</th><th class="num" title="Pareggiate">N</th><th class="num" title="Perse">P</th>
                <th class="num hide-sm" title="Gol fatti">GF</th><th class="num hide-sm" title="Gol subiti">GS</th><th class="num" title="Differenza reti">DR</th>
                <th class="hide-sm">Forma</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($standings as $row)
                <tr @class(['leader' => $loop->first && $row->played])>
                    <td class="pos">{{ $loop->iteration }}</td>
                    <td class="team-name"><span class="team-line"><x-team-badge :team="$row->team" size="sm" /> {{ $row->team->name }}</span></td>
                    <td class="num points">{{ $row->points }}</td>
                    <td class="num">{{ $row->played }}</td>
                    <td class="num">{{ $row->won }}</td>
                    <td class="num">{{ $row->drawn }}</td>
                    <td class="num">{{ $row->lost }}</td>
                    <td class="num hide-sm">{{ $row->goals_for }}</td>
                    <td class="num hide-sm">{{ $row->goals_against }}</td>
                    <td class="num">{{ $row->goal_difference > 0 ? '+' : '' }}{{ $row->goal_difference }}</td>
                    <td class="hide-sm"><span class="form">@foreach ($row->form as $r)<span class="form-{{ $r }}" title="{{ ['V' => 'Vinta', 'N' => 'Pareggiata', 'P' => 'Persa'][$r] }}">{{ $r }}</span>@endforeach</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
