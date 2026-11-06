<?php

namespace App\Models;

use App\Enums\Difficulty;
use Database\Factories\AnimeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $anilist_id
 * @property string $title
 * @property string|null $cover_path
 * @property int $popularity
 * @property Difficulty $difficulty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['anilist_id', 'title', 'cover_path', 'popularity', 'difficulty'])]
class Anime extends Model
{
    /** @use HasFactory<AnimeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'popularity' => 'integer',
            'difficulty' => Difficulty::class,
        ];
    }

    /**
     * @return HasMany<Character, $this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * Get the public URL of the cover image, if any.
     */
    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
