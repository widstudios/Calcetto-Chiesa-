{{--
    Regolamento proprio del torneo, scelto dall'admin al posto di quello CSI:
    testo scritto in "Modifica torneo" e/o PDF da scaricare.
--}}
<section class="card flush rules" id="regole">
    <div class="card-head"><h2><x-icon name="book" /> Regole di gioco</h2></div>
    <p class="rules-intro">
        Il <strong>{{ $tournament->name }}</strong> si gioca con il suo regolamento.
        @if ($tournament->rulesPdfUrl())
            <br><a class="btn small" href="{{ $tournament->rulesPdfUrl() }}" target="_blank" rel="noopener" style="margin-top: 10px"><x-icon name="book" /> Scarica il regolamento (PDF)</a>
        @endif
    </p>

    @if (filled($tournament->rules_text))
        <div class="rules-body">
            @foreach (App\Support\RulesText::blocks($tournament->rules_text) as $block)
                @if ($block['type'] === 'ul')
                    <ul class="rules-list">
                        @foreach ($block['lines'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>{!! implode('<br>', array_map('e', $block['lines'])) !!}</p>
                @endif
            @endforeach
        </div>
    @endif
</section>
