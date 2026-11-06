<?php

namespace App\Models;

use App\Enums\Difficulty;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $anilist_id
 * @property int $anime_id
 * @property string $name
 * @property string $search_name
 * @property list<string> $alternative_names
 * @property string $image_path
 * @property int $favourites
 * @property Difficulty $difficulty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Anime $anime
 */
#[Fillable(['anilist_id', 'anime_id', 'name', 'search_name', 'alternative_names', 'image_path', 'favourites', 'difficulty'])]
class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alternative_names' => 'array',
            'favourites' => 'integer',
            'difficulty' => Difficulty::class,
        ];
    }

    /**
     * @return BelongsTo<Anime, $this>
     */
    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * Normalize a name for accent and case insensitive searching.
     */
    public static function normalizeName(string $name): string
    {
        return Str::of($name)->ascii()->lower()->squish()->toString();
    }

    /**
     * Build the searchable text from the main and alternative names.
     *
     * @param  list<string>  $alternativeNames
     */
    public static function searchableText(string $name, array $alternativeNames): string
    {
        return collect([$name, ...$alternativeNames])
            ->map(fn (string $value): string => static::normalizeName($value))
            ->filter()
            ->unique()
            ->implode(' | ');
    }

    /**
     * Scope the query to characters whose names contain the given term.
     *
     * @param  Builder<Character>  $query
     */
    public function scopeMatching(Builder $query, string $term): void
    {
        $query->where('search_name', 'like', '%'.static::normalizeName($term).'%');
    }
}
