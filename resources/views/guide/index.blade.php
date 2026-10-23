@extends('layouts.app')

@section('title', 'Guida all\'app')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Per i mister</span>
            <h1>Guida all'app</h1>
            <p class="muted">Come si fa l'appello, si gestisce la rosa e si prepara la distinta, passo per passo.</p>
        </div>
    </div>

    <ul class="guide-toc">
        <li><a class="btn small secondary" href="#accesso">Accesso</a></li>
        <li><a class="btn small secondary" href="#menu">Menu</a></li>
        <li><a class="btn small secondary" href="#presenze">Presenze</a></li>
        <li><a class="btn small secondary" href="#rosa">Rosa</a></li>
        <li><a class="btn small secondary" href="#calendario">Calendario e distinte</a></li>
        <li><a class="btn small secondary" href="#esercizi">Esercizi e regolamento</a></li>
        <li><a class="btn small secondary" href="#domande">Domande frequenti</a></li>
    </ul>

    <section class="card guide" id="accesso">
        <h2><x-icon name="key" /> 1. Come si entra</h2>
        <p>Si entra da telefono o computer all'indirizzo <strong>{{ url('/login') }}</strong>, con la propria email e la password che ti ha dato l'organizzazione.</p>
        <ol>
            <li>Scrivi la tua <strong>email</strong>, quella che hai dato alla società, e la <strong>password</strong>.</li>
            <li>Se il telefono è solo tuo, spunta <strong>Ricordami su questo dispositivo</strong>: così non devi rientrare ogni volta.</li>
            <li>Tocca <strong>Entra</strong>.</li>
        </ol>
        <p>Se sbagli la password 5 volte di fila, l'app ti blocca per un minuto: aspetta e riprova.</p>

        <h3>Cambia subito la password</h3>
        <ol>
            <li>In alto a destra tocca l'icona della <strong>chiave</strong> (<a href="{{ route('password.edit') }}">Cambia password</a>).</li>
            <li>Scrivi la password attuale, poi due volte la nuova password, di almeno 8 caratteri.</li>
            <li>Tocca <strong>Salva</strong>.</li>
        </ol>
        <p>Per uscire tocca l'icona della <strong>porta con la freccia</strong> in alto a destra (Esci). Questa guida la ritrovi sempre dall'icona del <strong>punto di domanda</strong>.</p>
        <p class="hint">Consiglio: sul telefono puoi aggiungere l'app alla schermata Home. Su iPhone tocca Condividi e poi "Aggiungi alla schermata Home"; su Android tocca i tre puntini e poi "Aggiungi a schermata Home".</p>
    </section>

    <section class="card guide" id="menu">
        <h2><x-icon name="home" /> 2. La schermata iniziale e il menu</h2>
        <p>Dopo l'accesso si apre <strong>La mia squadra</strong>: per ogni squadra che alleni vedi la rosa e i pulsanti rapidi <strong>Calciatore</strong>, <strong>Gestisci rosa</strong>, <strong>Appello</strong> ed <strong>Esercizi</strong>.</p>
        <p>Sul telefono il menu è in basso, sul computer in alto:</p>
        <div class="table-wrap">
            <table class="stack">
                <thead><tr><th>Voce</th><th>A cosa serve</th></tr></thead>
                <tbody>
                    <tr><td class="title">Home</td><td data-label="A cosa serve">La tua squadra e i pulsanti rapidi</td></tr>
                    <tr><td class="title">La mia rosa</td><td data-label="A cosa serve">I calciatori, i loro dati e gli orari degli allenamenti</td></tr>
                    <tr><td class="title">Calendario</td><td data-label="A cosa serve">La settimana con allenamenti, partite e impegni</td></tr>
                    <tr><td class="title">Presenze</td><td data-label="A cosa serve">L'appello degli allenamenti e chi si allena di più</td></tr>
                    <tr><td class="title">Distinte</td><td data-label="A cosa serve">La lista dei convocati per la partita, da stampare o scaricare</td></tr>
                    <tr><td class="title">Esercizi</td><td data-label="A cosa serve">Gli esercizi con spiegazione, schema e video</td></tr>
                    <tr><td class="title">Documenti</td><td data-label="A cosa serve">I file da condividere: scegli se li vedono tutti o solo alcune persone</td></tr>
                </tbody>
            </table>
        </div>
        <p>Vedi solo le squadre che ti sono state assegnate. Se non ne vedi nessuna, scrivi all'organizzazione.</p>
    </section>

    <section class="card guide" id="presenze">
        <h2><x-icon name="checklist" /> 3. Presenze: come si fa l'appello</h2>
        <p>L'appello si fa solo nei giorni di allenamento della squadra, dal giorno stesso in poi: tutti partono "presenti" e tu tocchi <strong>Assente</strong> per chi manca.</p>

        <h3>Prima volta: inserisci gli orari degli allenamenti</h3>
        <p>Senza orari l'app non sa quando fare l'appello. Si inseriscono una volta sola.</p>
        <ol>
            <li>Vai su <a href="{{ route('teams.index') }}">La mia rosa</a> e scorri fino ad <strong>Allenamenti</strong>.</li>
            <li>Scegli <strong>Giorno</strong>, <strong>Campo</strong>, <strong>Dalle</strong> e <strong>Alle</strong> (per esempio martedì, campo oratorio, 17:00-18:30).</li>
            <li>Tocca <strong>Aggiungi allenamento</strong>. Ripeti per ogni giorno della settimana in cui vi allenate.</li>
        </ol>
        <p>Se un orario cambia, tocca <strong>Togli</strong> su quello vecchio e aggiungi il nuovo.</p>

        <h3>Ogni allenamento: l'appello</h3>
        <ol>
            <li>Tocca <a href="{{ route('attendances.index') }}">Presenze</a> nel menu e scegli la squadra (oppure <strong>Appello</strong> dalla Home o dal Calendario).</li>
            <li>Sotto <strong>Appello</strong> trovi il giorno di allenamento con la scritta gialla <strong>Appello da fare</strong>. Tocca <strong>Fai appello</strong>.</li>
            <li>Tutti i calciatori sono già segnati <strong>Presente</strong>. Tocca <strong>Assente</strong> solo per chi non c'è.</li>
            <li>In fondo, se vuoi, scegli dalle tendine gli <strong>esercizi fatti</strong> in allenamento.</li>
            <li>Tocca <strong>Salva appello</strong>. In alto compare "Appello del ... salvato".</li>
        </ol>
        <p>Se l'appello di oggi è già fatto, la riga mostra in grigio <strong>Prossimo appello</strong> con la data del prossimo allenamento: il pulsante comparirà quel giorno. Non si può fare l'appello di un giorno futuro.</p>

        <h3>Correggere o cancellare un appello</h3>
        <ul>
            <li>Nella stessa pagina, sotto <strong>Appelli fatti</strong>, trovi l'elenco con data e "presenti su totale". Tocca <strong>Modifica</strong>, cambia e salva di nuovo.</li>
            <li>Per eliminarlo del tutto, apri <strong>Modifica</strong> e in fondo tocca <strong>Cancella appello</strong>, poi conferma.</li>
        </ul>

        <h3>Vedere chi si allena di più</h3>
        <p>In fondo alla pagina <strong>Riepilogo per calciatore</strong> mostra per ognuno presenze, assenze e frequenza in percentuale. Con <strong>Scarica PDF</strong> in alto ottieni il foglio presenze completo da stampare o inviare.</p>
    </section>

    <section class="card guide" id="rosa">
        <h2><x-icon name="shirt" /> 4. La rosa: i tuoi calciatori</h2>
        <p>Nella pagina <a href="{{ route('teams.index') }}">La mia rosa</a> aggiungi, correggi e togli i calciatori della tua squadra, divisi tra <strong>Prima squadra</strong> e <strong>Panchina</strong>.</p>
        <h3>Aggiungere un calciatore</h3>
        <ol>
            <li>Tocca <strong>Aggiungi calciatore</strong> (o <strong>Calciatore</strong> dalla Home).</li>
            <li>Compila <strong>Nome</strong>, <strong>Cognome</strong> e <strong>Anno di nascita</strong> (obbligatori). Se vuoi, aggiungi <strong>Numero di maglia</strong>, <strong>Cellulare</strong> e <strong>Secondo cellulare</strong> (per esempio quello di un genitore).</li>
            <li>Scegli la <strong>Posizione in rosa</strong>: Prima squadra o Panchina.</li>
            <li>Tocca <strong>Salva</strong>, oppure <strong>Salva e aggiungi un altro</strong> per inserirne subito un altro.</li>
        </ol>
        <p><strong>Correggere o togliere:</strong> accanto a ogni calciatore ci sono <strong>Modifica</strong> e <strong>Togli</strong>. Togliere un calciatore lo cancella dalla rosa, quindi l'app chiede conferma.</p>
        <p>Con <strong>PDF rosa</strong> scarichi l'elenco della squadra da stampare. Il nome, lo stemma e i colori della squadra li cambia solo l'organizzazione.</p>
    </section>

    <section class="card guide" id="calendario">
        <h2><x-icon name="calendar" /> 5. Calendario e distinta di gara</h2>
        <h3>Calendario</h3>
        <p>Il <a href="{{ route('calendar.index') }}">Calendario</a> mostra una settimana alla volta con allenamenti, partite ed eventi, ognuno con il suo colore (la legenda è in fondo).</p>
        <ul>
            <li>Con le frecce passi alla settimana precedente o successiva; <strong>Oggi</strong> ti riporta a quella attuale.</li>
            <li>Sotto un allenamento di oggi o già passato c'è il pulsante <strong>Appello</strong>; sotto una partita c'è <strong>Distinta</strong>.</li>
            <li>Con <strong>Nuovo evento</strong> aggiungi un impegno: scegli titolo, tipo (Riunione, Incontro genitori, Formazione, Sponsor, Altro), squadra, giorno, orario, luogo e note, poi <strong>Salva</strong>. Gli eventi che hai creato li puoi modificare.</li>
        </ul>
        <p>Le partite le inserisce l'organizzazione; gli allenamenti compaiono da soli dagli orari messi nella rosa.</p>

        <h3>Distinta (lista convocati)</h3>
        <p>La distinta è il foglio con i convocati, numeri di maglia, capitano e vice da consegnare all'arbitro.</p>
        <ol>
            <li>Tocca <a href="{{ route('lineups.index') }}">Distinte</a> nel menu, scegli la squadra e poi <strong>Nuova distinta</strong> (oppure <strong>Distinta</strong> sotto la partita nel Calendario: data, avversario e campo si compilano da soli).</li>
            <li>Se serve, scegli la <strong>Partita del calendario</strong>. È facoltativa: per un'amichevole lasciala vuota e scrivi <strong>Data e ora</strong>, <strong>Avversario</strong> e <strong>Campo</strong>.</li>
            <li>In <strong>Chi va in campo</strong> sono già spuntati i calciatori della prima squadra. Spunta o togli i convocati e controlla il numero di maglia.</li>
            <li>Scegli il capitano (colonna <strong>C</strong>) e il vice (colonna <strong>VC</strong>).</li>
            <li>Tieni d'occhio il contatore "convocati su ... ammessi dal regolamento": se superi il massimo l'app non salva e ti dice quanti togliere.</li>
            <li>Tocca <strong>Salva distinta</strong>.</li>
        </ol>
        <p>Dall'elenco delle distinte puoi poi <strong>Stampare</strong>, scaricare il <strong>PDF</strong> (comodo da mandare su WhatsApp) o <strong>Modificare</strong>.</p>
    </section>

    <section class="card guide" id="esercizi">
        <h2><x-icon name="cone" /> 6. Esercizi, risultati e regolamento</h2>
        <p><strong>Esercizi.</strong> Nella voce <a href="{{ route('drills.index') }}">Esercizi</a> scegli il tipo di allenamento e poi l'esercizio: trovi come si fa, lo schema del campo e un video di esempio. Quando fai l'appello puoi segnare quali esercizi avete fatto, e nell'elenco <strong>Appelli fatti</strong> li ritrovi accanto a ogni data.</p>
        <p><strong>Risultati, classifica e marcatori.</strong> Li inserisce l'organizzazione dopo ogni partita. Tu e le famiglie li vedete sul <a href="{{ route('home') }}">sito pubblico</a>, senza bisogno di accedere.</p>
        <p><strong>Regolamento.</strong> Sul sito pubblico, nella voce <a href="{{ route('home') }}#regole">Regole</a>, c'è il regolamento del torneo da consultare anche a bordo campo.</p>
    </section>

    <section class="card guide" id="domande">
        <h2><x-icon name="help" /> 7. Domande frequenti</h2>
        <ul>
            <li><strong>Non vedo il pulsante "Fai appello".</strong> Controlla di aver inserito gli orari in La mia rosa, sotto Allenamenti, e che oggi sia un giorno di allenamento. Se l'appello di oggi è già fatto, lo correggi da Appelli fatti, con Modifica.</li>
            <li><strong>Ho fatto l'appello del giorno sbagliato.</strong> Apri Appelli fatti, tocca Modifica e poi Cancella appello.</li>
            <li><strong>Un ragazzo è arrivato a metà allenamento.</strong> Segnalo come presente: l'app conta solo presente o assente.</li>
            <li><strong>La distinta non si salva.</strong> Probabilmente hai spuntato più convocati di quelli ammessi: leggi il messaggio in rosso e togli quelli in più.</li>
            <li><strong>Ho dimenticato la password.</strong> Scrivi all'organizzazione: ti rimette una password provvisoria che poi cambi tu.</li>
            <li><strong>Non vedo la mia squadra.</strong> La squadra va collegata al tuo account dall'organizzazione: scrivile indicando la tua email.</li>
        </ul>
        <p>Per tutto il resto contatta l'organizzazione del torneo.</p>
    </section>
@endsection
