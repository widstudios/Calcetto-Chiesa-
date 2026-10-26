<?php

namespace App\Imports;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Mister con il tesseramento CSI, come il comando mister:importa.
 */
class CoachImporter extends Importer
{
    public function label(): string
    {
        return 'Allenatori';
    }

    public function description(): string
    {
        return 'Un allenatore per riga, riconosciuto dall\'email. Accede solo dopo che gli imposti una password da Allenatori → Modifica.';
    }

    public function columns(): array
    {
        return [
            'nome*' => 'Nome e cognome',
            'email*' => 'Email, serve per accedere',
            'tesserato_il' => 'Data del tesseramento CSI, es. 28/09/2026; vuota se è da tesserare',
        ];
    }

    public function example(): array
    {
        return [
            ['Mario Rossi', 'mario.rossi@example.com', '28/09/2026'],
            ['Luca Bianchi', 'luca.bianchi@example.com', ''],
        ];
    }

    public function route(): string
    {
        return route('coaches.index');
    }

    public function parse(array $row): array
    {
        $email = Str::lower($this->required($row, 'email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RowError("\"{$email}\" non è un'email valida.");
        }
        $registered = $this->date($row['tesserato_il'] ?? '', 'tesserato_il');
        if ($registered > now()->toDateString()) {
            throw new RowError('la data del tesseramento è nel futuro.');
        }
        if (User::whereRaw('lower(email) = ?', [$email])->whereNot(fn ($q) => $q->coaches())->exists()) {
            throw new RowError("{$email} appartiene a un account che non è un allenatore.");
        }

        return ['name' => $this->required($row, 'nome'), 'email' => $email, 'csi_registered_on' => $registered];
    }

    public function summary(array $data): string
    {
        return "{$data['name']} · {$data['email']} · ".($data['csi_registered_on']
            ? 'tesserato CSI dal '.date('d/m/Y', strtotime($data['csi_registered_on']))
            : 'da tesserare');
    }

    public function exists(array $data): bool
    {
        return $this->find($data) !== null;
    }

    public function save(array $data): bool
    {
        $coach = $this->find($data);
        if ($coach === null) {
            // Senza password nota: resta in elenco ma accede solo dopo che l'amministratore gliene imposta una.
            User::create([...$data, 'password' => Str::random(40), 'role' => Role::Allenatore]);

            return true;
        }

        // Una cella vuota non cancella un tesseramento già salvato.
        $coach->update(array_filter(['name' => $data['name'], 'csi_registered_on' => $data['csi_registered_on']]));

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function find(array $data): ?User
    {
        return User::whereRaw('lower(email) = ?', [$data['email']])->coaches()->first();
    }
}
