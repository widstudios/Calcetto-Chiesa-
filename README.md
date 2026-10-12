# Calcetto Chiesa

Gestionale per tornei di calcetto (Laravel 13).

## Cosa c'è

- **Sito pubblico** (`/`, senza login): classifica (3 punti a vittoria, 1 a pareggio), ultimi risultati con marcatori
  e migliore in campo, prossime partite, classifica marcatori, media voto, migliori in campo e calendario completo
  per giornata. Il pulsante *Area riservata* porta al login.
- **Accesso e ruoli**: login con email e password. Due ruoli: `admin` (amministratore del torneo) e `allenatore`.
  Il middleware `role` limita le rotte, es. `->middleware('role:admin')`.
- **Modello dati**
  - `Tournament`: nome, stagione, date di inizio e fine.
  - `Team`: appartiene a un torneo, con nome allenatore (`coach_name`) e, se serve, l'account dell'allenatore (`coach_id`).
  - `Player`: nome, cognome, anno di nascita, numero di maglia, prima squadra (`titolare`) o `panchina`.
  - `Performance`: riga del tabellino, cioè gol e voto di un calciatore in una partita; `Game.mvp_player_id` è il migliore in campo.
  - `Game`: una partita del calendario (giornata, data e ora, campo, risultato, stato). Si chiama `Game` perché `match` è una parola riservata in PHP.
- **Gestione dati** (menu in alto dopo l'accesso):
  - *Tornei*: crea, modifica, elimina.
  - *Squadre e rose*: crea le squadre con il nome dell'allenatore; nella pagina della squadra aggiungi i calciatori
    (nome, cognome, anno di nascita, numero, prima squadra o panchina).
  - *Allenatori*: crea gli account degli allenatori e assegna loro le squadre.
  - *Calendario*: aggiungi le partite di un torneo e, a partita giocata, il risultato.
  - *Tabellino* (dal calendario, sulle partite giocate): gol e voto di ogni calciatore e migliore in campo.
  - *Cambia password* (icona a forma di chiave in alto): ognuno cambia la propria.
  - L'allenatore vede solo le sue squadre e può gestirne la rosa; non può toccare tornei, altre squadre o calendario.

## Provarla con GitHub Codespaces

Dalla pagina del repository su GitHub: **Code → Codespaces → Create codespace** (scegli il branch da provare).
Al primo avvio vengono installate le dipendenze e creato il database con i dati di prova; poi parte il server
e si apre il browser sulla porta 8000. Se la scheda non si apre, vai su **Ports** e apri la porta 8000.
Per ripartire da zero con i dati di prova: `php artisan migrate:fresh --seed`.

## Avvio in locale

Servono PHP 8.3+ e Composer.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Apri http://127.0.0.1:8000 e accedi con uno degli utenti di prova (password `password`):

- `admin@calcetto.test` (amministratore)
- `allenatore@calcetto.test` (allenatore di "Oratorio United")

## Test

```sh
php artisan test
```
