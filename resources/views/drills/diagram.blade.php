{{-- Schema dell'esercizio visto dall'alto: coni arancioni, giocatori blu (rossi i difensori), palla bianca.
     Tratteggio = corsa, linea gialla con freccia = palla (conduzione, passaggio o tiro). --}}
<svg class="pitch" viewBox="0 0 320 200" role="img" aria-label="Schema: {{ App\Models\Drill::DIAGRAMS[$type] }}" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <marker id="dg-arrow-ball" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse"><path d="M0 0 10 5 0 10z" fill="#fde047"/></marker>
        <marker id="dg-arrow-run" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse"><path d="M0 0 10 5 0 10z" fill="#fff"/></marker>
        <symbol id="dg-cone" viewBox="-6 -7 12 12" width="12" height="12" overflow="visible"><path d="M0 -6 5 4H-5z" fill="#f97316" stroke="#7c2d12" stroke-width="1"/></symbol>
        <symbol id="dg-p" viewBox="-9 -9 18 18" width="18" height="18" overflow="visible"><circle r="8" fill="#1d4ed8" stroke="#fff" stroke-width="2"/></symbol>
        <symbol id="dg-d" viewBox="-9 -9 18 18" width="18" height="18" overflow="visible"><circle r="8" fill="#dc2626" stroke="#fff" stroke-width="2"/></symbol>
        <symbol id="dg-k" viewBox="-9 -9 18 18" width="18" height="18" overflow="visible"><circle r="8" fill="#facc15" stroke="#fff" stroke-width="2"/></symbol>
        <symbol id="dg-b" viewBox="-5 -5 10 10" width="10" height="10" overflow="visible"><circle r="4" fill="#fff" stroke="#0f172a" stroke-width="1.2"/></symbol>
    </defs>
    <rect width="320" height="200" rx="10" fill="#2f8f4e"/>
    <g fill="none" stroke="rgba(255,255,255,.55)" stroke-width="2">
        <rect x="8" y="8" width="304" height="184" rx="4"/>
    </g>
    @php
        $cone = fn ($x, $y) => '<use href="#dg-cone" x="'.($x - 6).'" y="'.($y - 6).'"/>';
        $p = fn ($x, $y, $t = 'p') => '<use href="#dg-'.$t.'" x="'.($x - 9).'" y="'.($y - 9).'"/>';
        $ball = fn ($x, $y) => '<use href="#dg-b" x="'.($x - 5).'" y="'.($y - 5).'"/>';
        $run = fn ($d) => '<path d="'.$d.'" fill="none" stroke="#fff" stroke-width="2" stroke-dasharray="6 5" marker-end="url(#dg-arrow-run)"/>';
        $pass = fn ($d) => '<path d="'.$d.'" fill="none" stroke="#fde047" stroke-width="2.5" marker-end="url(#dg-arrow-ball)"/>';
        $goal = fn ($x, $y, $h = 56, $w = 10) => '<rect x="'.$x.'" y="'.($y - $h / 2).'" width="'.$w.'" height="'.$h.'" fill="rgba(255,255,255,.25)" stroke="#fff" stroke-width="2.5"/>';
        $hoop = fn ($x, $y, $r = 11) => '<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" fill="none" stroke="#fde68a" stroke-width="2.5"/>';
    @endphp
    @switch($type)
        @case('slalom')
            @foreach ([90, 130, 170, 210, 250] as $x){!! $cone($x, 100) !!}@endforeach
            {!! $p(40, 100) !!}{!! $ball(56, 108) !!}
            {!! $pass('M58 108 C 75 140, 100 140, 110 100 S 140 60, 150 100 S 180 140, 190 100 S 220 60, 230 100 S 260 140, 280 110') !!}
            @break
        @case('zigzag')
            @foreach ([[80, 60], [130, 140], [180, 60], [230, 140], [280, 60]] as [$x, $y]){!! $cone($x, $y) !!}@endforeach
            {!! $p(36, 140) !!}{!! $ball(52, 136) !!}
            {!! $pass('M54 134 L 78 72 L 128 128 L 178 72 L 228 128 L 270 70') !!}
            @break
        @case('navetta')
            @foreach ([60, 130, 200, 270] as $x){!! $cone($x, 70) !!}{!! $cone($x, 130) !!}@endforeach
            {!! $p(40, 100) !!}
            {!! $run('M52 92 H 124') !!}{!! $run('M124 104 H 66') !!}
            {!! $run('M66 92 H 194') !!}{!! $run('M194 108 H 66') !!}
            {!! $run('M66 116 H 262') !!}
            <text x="160" y="182" text-anchor="middle" fill="#fff" font-size="12" font-family="sans-serif">vai, tocca la linea, torna</text>
            @break
        @case('otto')
            {!! $cone(110, 100) !!}{!! $cone(210, 100) !!}
            {!! $p(160, 158) !!}{!! $ball(160, 140) !!}
            {!! $pass('M160 132 C 130 70, 70 70, 70 100 S 130 130, 160 100 S 250 70, 250 100 S 190 130, 170 108') !!}
            @break
        @case('quadrato')
            @foreach ([[90, 45], [230, 45], [230, 155], [90, 155]] as [$x, $y]){!! $cone($x, $y) !!}{!! $p($x + ($x < 160 ? -18 : 18), $y) !!}@endforeach
            {!! $ball(80, 45) !!}
            {!! $pass('M84 40 H 206') !!}{!! $pass('M248 54 V 140') !!}{!! $pass('M236 160 H 112') !!}
            {!! $run('M72 52 C 60 100, 60 120, 72 146') !!}
            @break
        @case('coppie')
            @foreach ([60, 110, 160] as $y)
                {!! $p(60, $y) !!}{!! $p(260, $y) !!}{!! $cone(150, $y - 12) !!}{!! $cone(170, $y + 12) !!}
            @endforeach
            {!! $ball(76, 60) !!}
            {!! $pass('M82 60 H 242') !!}{!! $pass('M242 116 H 82') !!}
            @break
        @case('scaletta')
            <g stroke="#fde68a" stroke-width="3" fill="none">
                <path d="M60 80 H 260 M60 120 H 260"/>
                @foreach (range(60, 260, 25) as $x)<path d="M{{ $x }} 80 V 120"/>@endforeach
            </g>
            {!! $p(36, 100) !!}{!! $cone(290, 100) !!}
            {!! $run('M50 100 H 250') !!}
            <text x="160" y="160" text-anchor="middle" fill="#fff" font-size="12" font-family="sans-serif">un appoggio per ogni spazio, sulle punte</text>
            @break
        @case('percorso')
            {!! $p(30, 150) !!}
            @foreach ([50, 80, 110] as $x){!! $hoop($x, 110) !!}@endforeach
            @foreach ([150, 180, 210] as $x){!! $cone($x, 60) !!}@endforeach
            <path d="M232 132 H 256" stroke="#fde68a" stroke-width="4"/>
            {!! $goal(298, 100, 60) !!}
            {!! $run('M36 140 L 50 122 L 80 98 L 110 122 L 138 72') !!}
            {!! $pass('M140 66 C 160 40, 170 90, 190 50 S 210 70, 240 132') !!}
            {!! $pass('M258 122 L 292 100') !!}
            @break
        @case('tiro')
            {!! $goal(298, 100, 64) !!}{!! $p(306, 100, 'k') !!}
            {!! $p(60, 150) !!}{!! $ball(76, 146) !!}{!! $p(170, 50) !!}
            {!! $cone(200, 60) !!}{!! $cone(200, 140) !!}
            {!! $pass('M80 142 L 160 60') !!}{!! $run('M70 138 C 120 120, 170 120, 210 110') !!}
            {!! $pass('M176 60 L 204 104') !!}{!! $pass('M218 106 L 288 92') !!}
            @break
        @case('uno_contro_uno')
            {!! $goal(14, 100, 44, 8) !!}{!! $goal(298, 100, 44, 8) !!}
            {!! $cone(160, 20) !!}{!! $cone(160, 180) !!}
            {!! $p(80, 100) !!}{!! $ball(96, 104) !!}{!! $p(220, 100, 'd') !!}
            {!! $pass('M100 106 C 140 150, 200 160, 286 112') !!}{!! $run('M212 92 C 190 80, 160 90, 140 120') !!}
            @break
        @case('area_gioco')
            <rect x="50" y="30" width="220" height="140" fill="none" stroke="#fde68a" stroke-width="2" stroke-dasharray="4 4"/>
            @foreach ([[50, 30], [270, 30], [270, 170], [50, 170]] as [$x, $y]){!! $cone($x, $y) !!}@endforeach
            @foreach ([[90, 60], [150, 80], [220, 55], [110, 130], [190, 120], [240, 150], [80, 160]] as $i => [$x, $y])
                {!! $p($x, $y) !!}{!! $ball($x + 14, $y + 6) !!}
            @endforeach
            {!! $p(160, 150, 'k') !!}
            {!! $pass('M104 66 C 120 70, 130 90, 140 82') !!}{!! $pass('M204 126 C 220 110, 240 100, 250 80') !!}
            @break
        @case('tris')
            @foreach ([200, 230, 260] as $x)@foreach ([70, 100, 130] as $y){!! $hoop($x, $y, 12) !!}@endforeach @endforeach
            {!! $p(40, 70) !!}{!! $p(40, 130, 'd') !!}
            {!! $cone(60, 50) !!}{!! $cone(60, 150) !!}
            {!! $run('M52 70 C 100 60, 160 70, 186 96') !!}{!! $run('M52 130 C 110 150, 200 150, 248 132') !!}
            <text x="120" y="190" text-anchor="middle" fill="#fff" font-size="12" font-family="sans-serif">corri, posa la pettorina, torna: fai tris</text>
            @break
        @case('portiere')
            {!! $goal(300, 100, 80) !!}{!! $p(286, 100, 'k') !!}
            {!! $p(140, 50) !!}{!! $p(110, 100) !!}{!! $p(140, 150) !!}
            {!! $ball(156, 56) !!}{!! $ball(126, 100) !!}{!! $ball(156, 144) !!}
            {!! $pass('M160 60 L 280 78') !!}{!! $pass('M132 100 L 272 100') !!}{!! $pass('M160 140 L 280 122') !!}
            {!! $run('M286 112 V 140') !!}
            @break
    @endswitch
</svg>
