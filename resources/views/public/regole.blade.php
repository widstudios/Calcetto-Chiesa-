{{--
    Regole di gioco mostrate sulla pagina pubblica.
    Per modificarle basta cambiare il testo qui sotto: ogni <details> è una sezione apribile.
    Le voci con il bollino "Scelta del torneo" non sono fissate dal CSI: adattale al vostro torneo.
    Fonti: Oratorio Cup 2026/2027 del CSI Roma (https://www.csiroma.it/sport/attivita-parrocchiale/oratorio-cup/),
    norme tecniche CSI per il calcio a 5 ("Sport in Regola") e, per quanto non previsto,
    il Regolamento del Giuoco del Calcio a Cinque FIGC/AIA (ed. 2025) a cui il CSI rinvia.
--}}
@php($locale = '<span class="badge gold">Scelta del torneo</span>')

<section class="card flush rules" id="regole">
    <div class="card-head"><h2><x-icon name="book" /> Regole di gioco</h2></div>
    <p class="rules-intro">
        Si gioca come nell'<strong>Oratorio Cup del CSI Roma</strong>: le regole tecniche del calcio a 5 del Centro Sportivo Italiano (CSI), con lo spirito dell'oratorio.
        Le voci segnate {!! $locale !!} sono decise dall'organizzazione del torneo.
    </p>

    <details class="matchday" open>
        <summary>Lo spirito dell'Oratorio Cup</summary>
        <ul class="rules-list">
            <li><strong>Giocano tutti</strong>, a prescindere dal livello tecnico, fino all'ultima giornata.</li>
            <li>Ogni squadra indica un <strong>addetto all'arbitraggio</strong> (l'educatore-arbitro), formato dal CSI sul regolamento del calcio a 5, che dirige le partite giocate in casa.</li>
            <li>Allenatori, dirigenti e arbitro sono la <strong>"terza squadra in campo"</strong>, quella degli educatori: il rispetto delle regole e dell'avversario viene prima del risultato.</li>
            <li>Dopo il fischio finale c'è il <strong>quarto tempo</strong>: un momento insieme tra le due squadre per conoscersi.</li>
            <li>Alla <strong>festa finale</strong> giocano tutte le squadre, con partite che assegnano ogni posizione, dalla prima all'ultima.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Categorie e anni di nascita (2026/2027)</summary>
        <ul class="rules-list">
            <li><strong>Under 8</strong>: nati 2019-2021 · <strong>Under 9</strong>: 2018-2020, con fino a 3 sottoquota (2021) a partita.</li>
            <li><strong>Under 10</strong>: 2017-2019 · <strong>Under 11</strong>: 2016-2018 · <strong>Under 12</strong>: 2015-2017.</li>
            <li><strong>Under 13</strong> (Ragazzi): 2014-2016 · <strong>Under 14</strong> (Giovanissimi): 2013-2015.</li>
            <li><strong>Giovani</strong>: <strong>Under 15</strong> 2012-2014 · <strong>Under 16</strong> 2011-2013 · <strong>Under 17</strong> 2010-2012.</li>
            <li><strong>Adulti</strong>: <strong>Open</strong> nati nel 2011 e prima · <strong>Amatori</strong> nati nel 1992 e prima.</li>
            <li>Ragazzi, Giovanissimi, Giovani e Open possono schierare <strong>1 sottoquota a partita</strong>.</li>
            <li>Fino all'Under 17 le squadre possono essere <strong>miste</strong>, con al massimo <strong>2 ragazze fuori quota</strong> per categoria.</li>
            <li>Le squadre tutte femminili possono iscriversi nella <strong>categoria inferiore</strong> dall'Under 8 all'Under 11, e fino a <strong>due categorie inferiori</strong> dall'Under 12 all'Under 16.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Squadre e sostituzioni</summary>
        <ul class="rules-list">
            <li>In campo vanno <strong>5 giocatori per squadra</strong>, portiere compreso.</li>
            <li>Per iniziare la partita servono almeno <strong>3 giocatori</strong>. Se una squadra resta con meno di 3 giocatori, l'arbitro chiude la gara.</li>
            <li>In distinta si possono scrivere fino a <strong>12 giocatori</strong>. {!! $locale !!}</li>
            <li>Le sostituzioni sono <strong>"volanti" e illimitate</strong>: chi esce e chi entra passano dalla zona cambi davanti alla propria panchina. Chi esce deve essere fuori dal campo prima che l'altro entri.</li>
            <li>Una riserva può prendere il posto del portiere anche con un <strong>cambio volante</strong>. Se invece un giocatore già in campo si scambia con il portiere, bisogna aspettare il <strong>gioco fermo</strong> e avvisare l'arbitro.</li>
            <li>Il portiere deve avere una maglia di colore diverso dai compagni.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Durata della partita</summary>
        <ul class="rules-list">
            <li><strong>Due tempi da 20 minuti</strong> con 5 minuti di intervallo. {!! $locale !!}</li>
            <li>Ogni squadra ha <strong>un time-out di 1 minuto per tempo</strong>. Lo chiede l'allenatore (o il capitano) quando la squadra ha il pallone.</li>
            <li>Tempo di attesa: una squadra che non è pronta in campo <strong>15 minuti dopo l'orario</strong> perde la partita. {!! $locale !!}</li>
            <li>La distinta con i documenti va consegnata all'arbitro <strong>almeno 15 minuti prima</strong> dell'inizio.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Pallone, campo e abbigliamento</summary>
        <ul class="rules-list">
            <li>Si gioca con il <strong>pallone n. 4 a rimbalzo controllato</strong>.</li>
            <li>Il <strong>calcio di rigore</strong> si batte dal dischetto a <strong>6 metri</strong>; il <strong>tiro libero</strong> dal secondo dischetto a <strong>10 metri</strong>.</li>
            <li>I <strong>parastinchi sono obbligatori</strong> per tutti. Scarpe da calcetto con suola in gomma: niente tacchetti.</li>
            <li>Maglie dello stesso colore e numerate; le riserve in panchina indossano la pettorina.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Rimesse e calci piazzati</summary>
        <ul class="rules-list">
            <li><strong>Calcio d'inizio</strong> a pallone fermo al centro del campo, in qualsiasi direzione; gli avversari stanno ad almeno 3 metri.</li>
            <li><strong>Rimessa laterale con i piedi</strong>, a pallone fermo sulla linea, entro <strong>4 secondi</strong>. Gli avversari a 5 metri. Non si può segnare direttamente da rimessa laterale.</li>
            <li><strong>Rimessa dal fondo</strong>: la fa il portiere <strong>con le mani</strong>, dalla propria area, entro 4 secondi.</li>
            <li><strong>Calcio d'angolo</strong> con i piedi entro 4 secondi, avversari a 5 metri.</li>
            <li>Su ogni punizione la <strong>barriera</strong> sta ad almeno <strong>5 metri</strong>. Se si superano i 4 secondi, la palla passa agli avversari.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Il portiere</summary>
        <ul class="rules-list">
            <li>Il portiere può tenere il pallone nella propria metà campo per <strong>al massimo 4 secondi</strong>, con le mani o con i piedi.</li>
            <li><strong>Retropassaggio</strong>: dopo aver giocato il pallone, il portiere non può toccarlo di nuovo nella propria metà campo se prima non lo ha toccato un avversario.</li>
            <li>Il portiere non può prendere con le mani un pallone passato volontariamente con i piedi da un compagno.</li>
            <li>Fuori dalla propria area il portiere gioca come un giocatore di movimento.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Falli e falli cumulativi</summary>
        <ul class="rules-list">
            <li>Il calcetto è uno sport di <strong>contatto limitato</strong>: sono fallo spinte, trattenute, sgambetti e interventi pericolosi.</li>
            <li><strong>Vietati gli interventi in scivolata</strong> quando c'è un avversario che gioca il pallone. {!! $locale !!}</li>
            <li>I falli da punizione diretta si sommano: sono i <strong>falli cumulativi</strong>. Ogni squadra ne ha <strong>5 per tempo</strong>.</li>
            <li>Dal <strong>6° fallo</strong> in poi l'avversario batte un <strong>tiro libero senza barriera</strong> dal dischetto dei 10 metri, oppure dal punto del fallo se è più vicino alla porta.</li>
            <li>Un fallo da punizione diretta nella propria area vale il <strong>calcio di rigore</strong>.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Cartellini e disciplina</summary>
        <ul class="rules-list">
            <li><strong>Cartellino giallo</strong>: ammonizione. Due gialli nella stessa partita portano all'espulsione.</li>
            <li><strong>Cartellino azzurro</strong>: espulsione temporanea di <strong>4 minuti</strong>. La squadra gioca in quattro finché non scade il tempo o finché non subisce un gol. Ai fini della squalifica vale come due ammonizioni.</li>
            <li><strong>Cartellino rosso</strong>: espulsione definitiva e almeno una giornata di squalifica. Il giocatore non rientra: la squadra resta in inferiorità per <strong>2 minuti effettivi</strong>, o finché non subisce un gol, poi un compagno può entrare con il consenso dell'arbitro. {!! $locale !!}</li>
            <li>Quattro ammonizioni in partite diverse fanno scattare una giornata di squalifica. {!! $locale !!}</li>
            <li>Prima e dopo la partita le squadre si salutano e si stringono la mano: è il <strong>fair play</strong> dell'oratorio.</li>
        </ul>
    </details>

    <details class="matchday">
        <summary>Classifica e pareggi</summary>
        <ul class="rules-list">
            <li><strong>3 punti</strong> a chi vince, <strong>1 punto</strong> a testa per il pareggio, 0 a chi perde. {!! $locale !!}</li>
            <li>A parità di punti conta la <strong>differenza reti</strong>, poi i gol fatti.</li>
            <li>Nelle partite a eliminazione diretta, in caso di pareggio si tirano <strong>5 rigori per squadra</strong>, poi a oltranza. Possono tirare tutti i giocatori in distinta non espulsi. {!! $locale !!}</li>
        </ul>
    </details>

    <p class="small muted legend-line">
        Per tutto ciò che qui non è scritto valgono i documenti ufficiali dell'<a href="https://www.csiroma.it/sport/attivita-parrocchiale/oratorio-cup/" target="_blank" rel="noopener">Oratorio Cup del CSI Roma</a>, le norme tecniche CSI "Sport in Regola" e il regolamento di gioco del calcio a 5. Decide sempre l'arbitro.
    </p>
</section>
