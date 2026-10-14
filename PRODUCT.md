# Calcetto Chiesa

Scheda prodotto: chi usa il software, per fare cosa e con che tono. Serve come riferimento per ogni scelta di grafica e di testi.

## Cos'è

Il gestionale e il sito del torneo di calcetto della parrocchia (calcio a 5, regole CSI, oratorio).
Online su calcetto.widstudios.it. Tiene insieme categorie giovanili (Under 14, Under 17, ...), squadre,
allenatori (mister) tesserati CSI, rose con prima squadra e panchina, calendario, risultati, tabellino con gol e voti.

## Chi lo usa

- **Famiglie, ragazzi e tifosi** (sito pubblico, senza login). Arrivano quasi sempre dal telefono, spesso da un link
  girato su WhatsApp, a pochi giorni o poche ore dalla partita. Vogliono sapere: quando e dove si gioca, com'è finita,
  chi ha segnato, com'è la classifica. I ragazzi cercano il proprio nome tra marcatori e pagelle.
- **Amministratore del torneo** (gestionale). Volontario, non tecnico. Carica squadre e calendario (anche da Excel),
  inserisce risultati e tabellini dopo la partita, spesso dal telefono a bordo campo o la sera da casa.
- **Allenatori** (gestionale, solo le proprie squadre). Gestiscono la rosa, vedono orari di allenamento e partite.

## Superfici e modalità

| Superficie | Modalità | Cosa conta |
|---|---|---|
| Sito pubblico (home, classifiche, calendario, regole) | Persuadere e leggere | La prossima partita e l'ultimo risultato a colpo d'occhio; orgoglio di squadra; leggibile al sole sul telefono |
| Gestionale (tornei, squadre, rose, partite, tabellino, import) | Operare | Velocità di inserimento, bottoni grandi, nessun errore sui dati, chiaro cosa è stato salvato |

## Tono

- Italiano semplice e caldo, da oratorio: "Il torneo sta per cominciare: torna presto!", non linguaggio da ufficio.
- Mai gergo tecnico verso gli utenti. Si dice "giornata", "mister", "tabellino", "migliore in campo".
- Il fair play e lo spirito di comunità vengono prima della competizione: niente toni aggressivi o da scommesse.

## Identità visiva (attuale)

- Verde campo profondo e oro coppa; titoli Barlow Condensed in maiuscolo, testo Barlow.
- Linguaggio da campionato vero (tabellone, classifica, risultati in riquadro scuro) ma su scala di parrocchia:
  curato, non pretenzioso.
- Grafica in `public/css/app.css`, file statico senza build.

## Vincoli

- **Minori**: i calciatori sono spesso ragazzi. Sul sito pubblico solo nome, cognome e squadra; mai telefoni,
  date di nascita complete o altri dati personali. Le foto, se arriveranno, solo con consenso.
- **Telefono prima di tutto**: tutto deve funzionare a 360–390 px con il pollice, bersagli da almeno 44 px.
- **Hosting condiviso** (Netsons, cPanel): niente Node in produzione, niente servizi esterni a pagamento.
- **Gestione da volontari**: ogni funzione deve essere usabile senza istruzioni; quando servono, guide brevi in italiano.

## Obiettivi

1. Le famiglie trovano in pochi secondi data, ora e campo della prossima partita del proprio figlio.
2. Risultati e tabellini pubblicati la sera stessa della partita, inseriti in pochi minuti.
3. Un sito di cui ragazzi e parrocchia vadano fieri e che abbiano voglia di condividere.

## In sospeso

- Voti/pagelle: per ora inseriti nel tabellino; il sistema completo può aspettare.
- Da fare più avanti nel piano: CRM (contatti, sponsor) e CMS (notizie sul sito pubblico).
