#!/usr/bin/env bash
# Prepara l'app al primo avvio del Codespace: dipendenze, .env, database SQLite con dati di prova.
set -euo pipefail

composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

touch database/database.sqlite
php artisan migrate:fresh --seed --force
