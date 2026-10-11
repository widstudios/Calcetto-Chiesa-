#!/usr/bin/env bash
# Prepara l'app al primo avvio del Codespace: dipendenze, .env, database SQLite con dati di prova.
set -euo pipefail

composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# In Codespaces il sito passa dal proxy https di GitHub: l'app deve fidarsene.
if [ "${CODESPACES:-}" = "true" ]; then
    sed -i '/^TRUSTED_PROXIES=/d' .env
    echo 'TRUSTED_PROXIES=*' >> .env
fi

touch database/database.sqlite
php artisan migrate:fresh --seed --force
