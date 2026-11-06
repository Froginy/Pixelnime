<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class AniListClient
{
    public const int PER_PAGE = 50;

    private const string POPULAR_CHARACTERS_QUERY = <<<'GRAPHQL'
        query ($page: Int, $perPage: Int) {
          Page(page: $page, perPage: $perPage) {
            pageInfo { hasNextPage }
            characters(sort: FAVOURITES_DESC) {
              id
              favourites
              name { full alternative }
              image { large }
              media(type: ANIME, sort: POPULARITY_DESC, perPage: 1) {
                nodes {
                  id
                  popularity
                  title { romaji english }
                  coverImage { large }
                }
              }
            }
          }
        }
        GRAPHQL;

    private const string SEARCH_ANIME_QUERY = <<<'GRAPHQL'
        query ($search: String) {
          Media(search: $search, type: ANIME, sort: SEARCH_MATCH) {
            id
            popularity
            title { romaji english }
            coverImage { large }
          }
        }
        GRAPHQL;

    private const string ANIME_CHARACTERS_QUERY = <<<'GRAPHQL'
        query ($id: Int, $page: Int, $perPage: Int) {
          Media(id: $id, type: ANIME) {
            characters(page: $page, perPage: $perPage, sort: [FAVOURITES_DESC]) {
              pageInfo { hasNextPage }
              edges {
                role
                node {
                  id
                  favourites
                  name { full alternative }
                  image { large }
                }
              }
            }
          }
        }
        GRAPHQL;

    /**
     * Fetch one page of characters, ordered from the most to the least favourited.
     *
     * @return array{hasNextPage: bool, characters: list<array{id: int, favourites: int|null, name: array{full: string|null, alternative: list<string|null>|null}, image: array{large: string|null}, media: array{nodes: list<array{id: int, popularity: int|null, title: array{romaji: string|null, english: string|null}, coverImage: array{large: string|null}}>}}>}
     */
    public function popularCharacters(int $page): array
    {
        $response = $this->query(self::POPULAR_CHARACTERS_QUERY, ['page' => $page, 'perPage' => self::PER_PAGE]);

        return [
            'hasNextPage' => (bool) $response->json('data.Page.pageInfo.hasNextPage'),
            'characters' => $response->json('data.Page.characters', []),
        ];
    }

    /**
     * Fetch one page of the characters of an anime, ordered from the most to the least favourited.
     *
     * @return array{hasNextPage: bool, characters: list<array{role: string, node: array{id: int, favourites: int|null, name: array{full: string|null, alternative: list<string|null>|null}, image: array{large: string|null}}}>}
     */
    public function animeCharacters(int $animeId, int $page): array
    {
        $response = $this->query(self::ANIME_CHARACTERS_QUERY, ['id' => $animeId, 'page' => $page, 'perPage' => 25]);

        return [
            'hasNextPage' => (bool) $response->json('data.Media.characters.pageInfo.hasNextPage'),
            'characters' => $response->json('data.Media.characters.edges', []),
        ];
    }

    /**
     * Find the anime that best matches a title.
     *
     * @return array{id: int, popularity: int|null, title: array{romaji: string|null, english: string|null}, coverImage: array{large: string|null}}|null
     */
    public function searchAnime(string $title): ?array
    {
        return $this->query(self::SEARCH_ANIME_QUERY, ['search' => $title])->json('data.Media');
    }

    /**
     * Send a GraphQL query, waiting as long as AniList asks when its rate limit is reached.
     *
     * @param  array<string, int|string>  $variables
     */
    private function query(string $query, array $variables): Response
    {
        return Http::acceptJson()
            ->retry(5, function (int $attempt, Throwable $exception): int {
                if ($exception instanceof RequestException && $exception->response->status() === 429) {
                    return ((int) $exception->response->header('Retry-After') ?: 60) * 1000;
                }

                return 2000 * $attempt;
            })
            ->post(config('services.anilist.url'), ['query' => $query, 'variables' => $variables])
            ->throw();
    }

    /**
     * Download an image hosted on the AniList CDN.
     */
    public function downloadImage(string $url): string
    {
        return Http::retry(3, 500)->get($url)->throw()->body();
    }
}
