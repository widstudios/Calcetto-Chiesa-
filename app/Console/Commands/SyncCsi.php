<?php

namespace App\Console\Commands;

use App\Support\CsiSync;
use Illuminate\Console\Command;
use RuntimeException;

class SyncCsi extends Command
{
    protected $signature = 'csi:sync';

    protected $description = 'Scarica i comunicati nuovi dalla cartella Google Drive del CSI Roma (Oratorio Cup)';

    public function handle(CsiSync $sync): int
    {
        try {
            $summary = $sync->run();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("File nella cartella: {$summary['total']}. Nuovi: {$summary['new']}. Aggiornati: {$summary['updated']}.");

        return self::SUCCESS;
    }
}
