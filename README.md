# Calcetto Chiesa

Gestionale per tornei di calcetto (Laravel 13).

## Cosa c'è

- **Accesso e ruoli**: login con email e password. Due ruoli: `admin` (amministratore del torneo) e `allenatore`.
  Il middleware `role` limita le rotte, es. `->middleware('role:admin')`.
- **Modello dati**
  - `Tournament`: nome, stagione, date di inizio e fine.
  - `Team`: appartiene a un torneo, con nome allenatore (`coach_name`) e, se serve, l'account dell'allenatore (`coach_id`).
  - `Player`: nome, cognome, anno di nascita, numero di maglia, prima squadra (`titolare`) o `panchina`.
  - `Game`: una partita del calendario (giornata, data e ora, campo, risultato, stato). Si chiama `Game` perché `match` è una parola riservata in PHP.
- **Pannello**: l'amministratore vede tornei e prossime partite; l'allenatore vede la rosa della propria squadra.

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
