#!/bin/bash
# Script di deploy per Cloudways / Server Linux

# Assicurati di essere nella root del progetto
# cd /path/to/project

# Abilita modalità manutenzione
php artisan down

# Pull del codice
git pull origin main

# Installazione dipendenze
composer install --no-interaction --prefer-dist --optimize-autoloader

# Migrazioni database
php artisan migrate --force

# Upgrade Filament (assets, etc)
php artisan filament:upgrade

# Pulizia cache
php artisan optimize:clear

# Ricostruzione cache
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Disabilita modalità manutenzione
php artisan up

echo "Deploy completato con successo!"



