<?php

use App\Enums\Difficulty;
use App\Models\Anime;
use App\Models\Character;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

function aniListCharacter(int $id, int $favourites, ?int $animeId, int $animePopularity = 1000): array
{
    return [
        'id' => $id,
        'favourites' => $favourites,
        'name' => ['full' => "Character {$id}", 'alternative' => ["Alias {$id}", '']],
        'image' => ['large' => "https://s4.anilist.co/file/character/{$id}.png"],
        'media' => ['nodes' => $animeId === null ? [] : [[
            'id' => $animeId,
            'popularity' => $animePopularity,
            'title' => ['romaji' => "Romaji {$animeId}", 'english' => null],
            'coverImage' => ['large' => "https://s4.anilist.co/file/cover/{$animeId}.jpg"],
        ]]],
    ];
}

it('imports characters with their anime, images and popularity tiers', function () {
    Storage::fake('local');
    Storage::fake('public');
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'graphql.anilist.co' => Http::response(['data' => ['Page' => [
            'pageInfo' => ['hasNextPage' => false],
            'characters' => [
                aniListCharacter(1, 900, 10, 5000),
                aniListCharacter(2, 600, 10, 5000),
                aniListCharacter(3, 300, 20, 100),
                aniListCharacter(4, 100, null),
            ],
        ]]]),
        's4.anilist.co/*' => Http::response('image-bytes'),
    ]);

    $this->artisan('pixelnime:import', ['--limit' => 10, '--per-anime' => 0])->assertSuccessful();

    expect(Character::query()->orderByDesc('favourites')->pluck('difficulty')->all())
        ->toBe([Difficulty::Easy, Difficulty::Medium, Difficulty::Hard])
        ->and(Anime::query()->orderByDesc('popularity')->pluck('difficulty')->all())
        ->toBe([Difficulty::Easy, Difficulty::Hard])
        ->and(Character::query()->firstWhere('anilist_id', 1))
        ->search_name->toBe('character 1 | alias 1')
        ->alternative_names->toBe(['Alias 1']);

    Storage::disk('local')->assertExists('characters/1.png');
    Storage::disk('public')->assertExists('covers/10.jpg');
});

it('stops once the limit is reached', function () {
    Storage::fake('local');
    Storage::fake('public');
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'graphql.anilist.co' => Http::response(['data' => ['Page' => [
            'pageInfo' => ['hasNextPage' => true],
            'characters' => [aniListCharacter(1, 900, 10), aniListCharacter(2, 600, 10)],
        ]]]),
        's4.anilist.co/*' => Http::response('image-bytes'),
    ]);

    $this->artisan('pixelnime:import', ['--limit' => 1, '--per-anime' => 0])->assertSuccessful();

    expect(Character::query()->count())->toBe(1);
    Sleep::assertNeverSlept();
});

it('completes each anime with its popular main and supporting characters', function () {
    Storage::fake('local');
    Storage::fake('public');
    Sleep::fake();
    Http::preventStrayRequests();

    $edge = fn (int $id, int $favourites, string $role = 'SUPPORTING', ?string $image = null): array => [
        'role' => $role,
        'node' => [
            'id' => $id,
            'favourites' => $favourites,
            'name' => ['full' => "Character {$id}", 'alternative' => []],
            'image' => ['large' => $image ?? "https://s4.anilist.co/file/character/{$id}.png"],
        ],
    ];

    Http::fake([
        'graphql.anilist.co' => function (Request $request) use ($edge) {
            if (str_contains($request['query'], 'Media(id')) {
                return Http::response(['data' => ['Media' => ['characters' => [
                    'pageInfo' => ['hasNextPage' => true],
                    'edges' => [
                        $edge(1, 900, 'MAIN'),
                        $edge(2, 500),
                        $edge(3, 400, 'BACKGROUND'),
                        $edge(4, 300, 'SUPPORTING', 'https://s4.anilist.co/file/character/large/default.jpg'),
                        $edge(5, 50),
                        $edge(6, 5),
                    ],
                ]]]]);
            }

            return Http::response(['data' => ['Page' => [
                'pageInfo' => ['hasNextPage' => false],
                'characters' => [aniListCharacter(1, 900, 10)],
            ]]]);
        },
        's4.anilist.co/*' => Http::response('image-bytes'),
    ]);

    $this->artisan('pixelnime:import', ['--limit' => 1, '--min-favourites' => 10])->assertSuccessful();

    expect(Character::query()->orderBy('anilist_id')->pluck('anilist_id')->all())->toBe([1, 2, 5])
        ->and(Character::query()->distinct()->pluck('anime_id')->all())->toHaveCount(1);
    Http::assertSentCount(2 + 4);
});

it('keeps a character in the anime it was first imported with', function () {
    Storage::fake('local');
    Storage::fake('public');
    Sleep::fake();
    Http::preventStrayRequests();
    $original = Anime::factory()->create(['anilist_id' => 99, 'popularity' => 1]);
    $character = Character::factory()->for($original)->create(['anilist_id' => 1]);
    storeCharacterImage($character);

    Http::fake([
        'graphql.anilist.co' => Http::response(['data' => ['Page' => [
            'pageInfo' => ['hasNextPage' => false],
            'characters' => [aniListCharacter(1, 900, 10, 5000)],
        ]]]),
        's4.anilist.co/*' => Http::response('image-bytes'),
    ]);

    $this->artisan('pixelnime:import', ['--limit' => 1, '--per-anime' => 0])->assertSuccessful();

    expect($character->fresh()->anime_id)->toBe($original->id);
});

it('imports only the requested animes with their characters', function () {
    Storage::fake('local');
    Storage::fake('public');
    Sleep::fake();
    Http::preventStrayRequests();

    Http::fake([
        'graphql.anilist.co' => function (Request $request) {
            if (str_contains($request['query'], 'search')) {
                return Http::response(['data' => ['Media' => [
                    'id' => 178025,
                    'popularity' => 267995,
                    'title' => ['romaji' => 'Gachiakuta', 'english' => 'Gachiakuta'],
                    'coverImage' => ['large' => 'https://s4.anilist.co/file/cover/178025.jpg'],
                ]]]);
            }

            return Http::response(['data' => ['Media' => ['characters' => [
                'pageInfo' => ['hasNextPage' => false],
                'edges' => [
                    ['role' => 'MAIN', 'node' => ['id' => 7, 'favourites' => 2000, 'name' => ['full' => 'Rudo Surebrec', 'alternative' => []], 'image' => ['large' => 'https://s4.anilist.co/file/character/7.png']]],
                ],
            ]]]]);
        },
        's4.anilist.co/*' => Http::response('image-bytes'),
    ]);

    $this->artisan('pixelnime:import', ['--anime' => ['Gachiakuta']])->assertSuccessful();

    expect(Anime::query()->sole()->title)->toBe('Gachiakuta')
        ->and(Character::query()->sole()->name)->toBe('Rudo Surebrec');
    Http::assertSentCount(4);
});
