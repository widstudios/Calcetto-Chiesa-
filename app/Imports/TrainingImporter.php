<?php

namespace App\Imports;

use App\Models\Training;

class TrainingImporter extends Importer
{
    public function label(): string
    {
        return 'Allenamenti';
    }

    public function description(): string
    {
        return 'Un allenamento settimanale per riga. Lo stesso giorno e ora d\'inizio della stessa squadra viene aggiornato.';
    }

    public function columns(): array
    {
        return [
            'torneo*' => 'Nome del torneo o della categoria',
            'stagione' => 'Serve solo se lo stesso torneo ha più stagioni',
            'squadra*' => 'Nome della squadra, già caricata',
            'giorno*' => 'Es. martedì',
            'campo*' => 'Dove si allenano',
            'inizio*' => 'Es. 18:30',
            'fine*' => 'Es. 20:00',
        ];
    }

    public function example(): array
    {
        return [
            ['Torneo di Primavera', '2026/27', 'Leoni', 'martedì', 'Campo parrocchiale', '18:30', '20:00'],
            ['Torneo di Primavera', '2026/27', 'Leoni', 'giovedì', 'Campo parrocchiale', '18:30', '20:00'],
        ];
    }

    public function route(): string
    {
        return route('teams.index');
    }

    public function parse(array $row): array
    {
        $data = $this->teamColumns($row) + [
            'weekday' => $this->weekday($this->required($row, 'giorno')),
            'field' => $this->required($row, 'campo'),
            'starts_at' => $this->time($this->required($row, 'inizio'), 'inizio'),
            'ends_at' => $this->time($this->required($row, 'fine'), 'fine'),
        ];
        if ($data['ends_at'] <= $data['starts_at']) {
            throw new RowError('l\'orario di fine deve venire dopo quello di inizio.');
        }
        $this->existingTeam($data);

        return $data;
    }

    public function summary(array $data): string
    {
        return "{$data['squadra']} · ".Training::WEEKDAYS[$data['weekday']]." {$data['starts_at']} - {$data['ends_at']} · {$data['field']}";
    }

    public function exists(array $data): bool
    {
        return $this->find($data) !== null;
    }

    public function save(array $data): bool
    {
        $training = $this->find($data);
        if ($training === null) {
            $this->existingTeam($data)->trainings()->create(collect($data)->only(['weekday', 'field', 'starts_at', 'ends_at'])->all());

            return true;
        }
        $training->update(['field' => $data['field'], 'ends_at' => $data['ends_at']]);

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function find(array $data): ?Training
    {
        return Training::where('team_id', $this->existingTeam($data)->id)
            ->where('weekday', $data['weekday'])
            ->whereIn('starts_at', [$data['starts_at'], $data['starts_at'].':00'])
            ->first();
    }
}
