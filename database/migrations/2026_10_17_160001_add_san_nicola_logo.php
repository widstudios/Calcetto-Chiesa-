<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Stemma del C.S.P. San Nicola, colori rosso e bianco, sulle squadre che si chiamano
     * San Nicola e non hanno ancora un logo. Se la squadra non c'è non fa niente.
     */
    public function up(): void
    {
        $teams = DB::table('teams')->whereNull('logo')->whereRaw('lower(name) like ?', ['%san nicola%'])->get();

        foreach ($teams as $team) {
            $path = Storage::disk('uploads')->putFileAs('', resource_path('loghi/san-nicola.jpg'), 'squadra-'.$team->id.'-'.Str::lower(Str::random(8)).'.jpg');

            // Se la cartella non è scrivibile restano almeno i colori: il logo si carica a mano da "Modifica squadra".
            DB::table('teams')->where('id', $team->id)->update([
                'logo' => $path ?: null,
                'shirt_color' => $team->shirt_color ?? '#dc2626',
                'shirt_color_secondary' => $team->shirt_color_secondary ?? '#ffffff',
            ]);
        }
    }

    public function down(): void
    {
        // Il logo resta: si toglie da "Modifica squadra".
    }
};
