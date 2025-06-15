<?php

namespace Apsonex\LaravelStockImage\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Apsonex\LaravelStockImage\Services\Providers\Pexels;
use Apsonex\LaravelStockImage\Services\Providers\Pixabay;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;
use Apsonex\LaravelStockImage\Services\Providers\Unsplash;
use Apsonex\LaravelStockImage\Services\Providers\Placeholder;

/**
 * Image Search Service with Multiple API Provider Support
 *
 * Implements SOLID principles with clean separation of concerns
 * Shuffles API providers per keyword for load distribution
 */
class ImageSearchServiceOld
{
    protected const MAX_RETRIES = 1;

    protected array $providers;

    protected ?array $payload;

    public function __construct()
    {
        $this->providers = [
            'pexels'   => [$this, 'searchPexels'],
            'unsplash' => [$this, 'searchUnsplash'],
            'pixabay'  => [$this, 'searchPixabay'],
        ];
    }

    public function prepare(array $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    public function credentials(): array
    {
        return $this->payload['credentials'];
    }

    public function randomResult(): bool
    {
        return $this->payload['randomResult'] === true;
    }

    public function randomProvider(): bool
    {
        return $this->payload['randomProvider'] === true;
    }

    /**
     * Search for an image using the provided keyword
     *
     * @param string $keyword
     * @return array|null
     */
    public function search(string $keyword): ?array
    {
        // Shuffle providers for each keyword to distribute load
        $shuffledProviders = $this->providers;
        $providerKeys      = array_keys($shuffledProviders);

        if ($this->randomProvider()) {
            shuffle($providerKeys);
        }

        foreach ($providerKeys as $providerName) {
            try {
                $result = Cache::get($this->cacheKey($keyword));
                $hasCache = filled($result);

                $isValidCache = is_array($result) && Arr::get($result, '0.image_url');

                if ($isValidCache) {
                    $result = collect($result);

                    return [
                        'images' => $this->randomResult() ? $result->shuffle()->take(2)->toArray() : $result->take(2)->toArray(),
                        'keyword_used' => $keyword
                    ];
                } else if ($hasCache) {
                    Cache::forget($this->cacheKey($keyword));
                }

                $result = $this->searchWithProvider($providerName, $keyword);

                if ($result) {
                    Cache::put($this->cacheKey($keyword), $result->toArray(), now()->addDays(2));

                    $response = [
                        'images'       => $this->randomResult() ? $result->shuffle()->take(2)->toArray() : $result->take(2)->toArray(),
                        'keyword_used' => $keyword,
                    ];

                    return $response;
                }
            } catch (\Exception $e) {
                Log::warning("Image search failed for provider {$providerName}", [
                    'keyword' => $keyword,
                    'error'   => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * Search with specific provider
     */
    protected function searchWithProvider(string $providerName, string $keyword): ?Collection
    {
        $searchMethod = $this->providers[$providerName];

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result = call_user_func($searchMethod, $keyword);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                if ($attempt === self::MAX_RETRIES) {
                    throw $e;
                }
                usleep(500000); // 0.5 second delay between retries
            }
        }

        return null;
    }

    /**
     * Search Pexels API
     */
    protected function searchPexels(string $keyword): SearchResponse
    {
        return Pexels::make()
            ->cache($this->payload['cache'] === true)
            ->timeout($this->payload['timeout'] ?? 60)
            ->page($this->payload['page'] ?? 1)
            ->apiKey($this->payload['credentials']['pexels'])
            ->cachedOrQuery(keyword: $keyword);
    }

    /**
     * Search Unsplash API
     */
    protected function searchUnsplash(string $keyword): SearchResponse
    {
        return Unsplash::make()
            ->cache($this->payload['cache'] === true)
            ->timeout($this->payload['timeout'] ?? 60)
            ->page($this->payload['page'] ?? 1)
            ->apiKey($this->payload['credentials']['unsplash'])
            ->cachedOrQuery($keyword);
    }

    /**
     * Search Pixabay API
     */
    protected function searchPixabay(string $keyword): SearchResponse
    {
        return Pixabay::make()
            ->cache($this->payload['cache'] === true)
            ->timeout($this->payload['timeout'] ?? 60)
            ->page($this->payload['page'] ?? 1)
            ->apiKey($this->payload['credentials']['pixabay'])
            ->cachedOrQuery($keyword);
    }

    protected function cacheKey($keyword)
    {
        return 'image-search:key-' . Str::slug($keyword);
    }

    public function getFallbackImage(string $keyword): SearchResponse
    {
        return Placeholder::make()
            ->size($this->payload['placeholderSize'] ?? '600x400')
            ->text($this->payload['placeholderText'] ?? 'Sample Text')
            ->bgColor($this->payload['placeholderBgColor'] ?? '#cccccc')
            ->textColor($this->payload['placeholderTextColor'] ?? '#000000')
            ->generate($keyword);
    }
}
