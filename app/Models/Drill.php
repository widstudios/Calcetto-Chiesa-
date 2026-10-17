<?php

namespace App\Models;

use App\Enums\DrillCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Esercizio della libreria allenamenti: a cosa serve, come si prepara e si svolge, con video di esempio.
 */
#[Fillable(['slug', 'category', 'name', 'age_min', 'age_max', 'minutes', 'players', 'equipment', 'description', 'setup', 'steps', 'tips', 'diagram', 'video_url', 'video_title', 'video_channel'])]
class Drill extends Model
{
    /**
     * Schemi disegnati disponibili (resources/views/drills/diagram.blade.php).
     */
    public const DIAGRAMS = [
        'slalom' => 'Slalom tra i coni',
        'zigzag' => 'Zig-zag con cambi di direzione',
        'navetta' => 'Navetta avanti e indietro',
        'otto' => 'Percorso a otto',
        'quadrato' => 'Quadrato con 4 giocatori',
        'coppie' => 'Coppie con porticina',
        'scaletta' => 'Scaletta di coordinazione',
        'percorso' => 'Percorso con cerchi, ostacoli e tiro',
        'tiro' => 'Passaggio e tiro in porta',
        'uno_contro_uno' => '1 contro 1 alle porticine',
        'area_gioco' => 'Area di gioco con tutti dentro',
        'tris' => 'Tris con i cerchi',
        'portiere' => 'Portiere in porta',
    ];

    /**
     * Link YouTube accettati: watch?v=, youtu.be/, embed/, shorts/, live/.
     */
    public const YOUTUBE_PATTERN = '~^https?://(?:www\.|m\.)?(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~';

    protected function casts(): array
    {
        return [
            'category' => DrillCategory::class,
            'age_min' => 'integer',
            'age_max' => 'integer',
            'minutes' => 'integer',
        ];
    }

    public function youtubeId(): ?string
    {
        return $this->video_url && preg_match(self::YOUTUBE_PATTERN, $this->video_url, $m) ? $m[1] : null;
    }

    /**
     * Video incorporato senza cookie di profilazione finché non si preme play.
     */
    public function embedUrl(): ?string
    {
        return ($id = $this->youtubeId()) ? 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0' : null;
    }

    public function watchUrl(): ?string
    {
        return ($id = $this->youtubeId()) ? 'https://www.youtube.com/watch?v='.$id : null;
    }

    public function ageLabel(): string
    {
        return $this->age_min === $this->age_max ? $this->age_min.' anni' : $this->age_min.'-'.$this->age_max.' anni';
    }

    /**
     * @return Collection<int, string>
     */
    public function stepList(): Collection
    {
        return self::lines($this->steps);
    }

    /**
     * @return Collection<int, string>
     */
    public function tipList(): Collection
    {
        return self::lines($this->tips);
    }

    /**
     * Esercizi ordinati per tipo (nell'ordine dell'elenco) e nome.
     *
     * @return Collection<string, Collection<int, Drill>>
     */
    public static function grouped(?Collection $drills = null): Collection
    {
        $byCategory = ($drills ?? self::orderBy('name')->get())->groupBy(fn (Drill $d) => $d->category->value);

        return collect(DrillCategory::cases())
            ->filter(fn (DrillCategory $c) => $byCategory->has($c->value))
            ->mapWithKeys(fn (DrillCategory $c) => [$c->value => $byCategory->get($c->value)]);
    }

    /**
     * @return Collection<int, string>
     */
    private static function lines(?string $text): Collection
    {
        return collect(preg_split('/\R/', (string) $text))->map(fn ($line) => trim($line))->filter()->values();
    }
}
