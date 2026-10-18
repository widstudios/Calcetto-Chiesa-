<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Imposta la stessa password a tutti gli account allenatore, così possono accedere.
 *
 * La password si passa da riga di comando e non è scritta nel codice. Con --escludi si
 * lasciano com'erano i mister indicati: basta una parola del nome (es. --escludi=Mirko).
 */
class SetCoachPasswords extends Command
{
    protected $signature = 'mister:password
        {password : Password da impostare ai mister}
        {--escludi=* : Mister da non toccare (una parola del nome, es. Mirko)}';

    protected $description = 'Imposta la stessa password a tutti i mister, tranne quelli esclusi';

    public function handle(): int
    {
        $password = $this->argument('password');
        if (Str::length($password) < 8) {
            $this->error('La password deve avere almeno 8 caratteri.');

            return self::FAILURE;
        }

        $excluded = array_map(fn ($name) => Str::lower(trim($name)), $this->option('escludi'));
        $updated = $skipped = [];

        foreach (User::where('role', Role::Allenatore)->orderBy('name')->get() as $coach) {
            $words = preg_split('/\s+/', Str::lower($coach->name));
            if (array_intersect($excluded, $words) !== []) {
                $skipped[] = $coach->name;

                continue;
            }
            $coach->update(['password' => $password]);
            $updated[] = $coach->name;
        }

        $this->info(count($updated).' mister aggiornati: '.($updated === [] ? 'nessuno' : implode(', ', $updated)).'.');
        $this->info('Esclusi: '.($skipped === [] ? 'nessuno' : implode(', ', $skipped)).'.');

        return self::SUCCESS;
    }
}
