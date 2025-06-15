<?php

namespace Apsonex\LaravelStockImage\Services;

use Apsonex\LaravelStockImage\Services\Providers\Pexels;
use Apsonex\LaravelStockImage\Services\Providers\Pixabay;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;
use Apsonex\LaravelStockImage\Services\Providers\Unsplash;
use Apsonex\LaravelStockImage\Services\Providers\Placeholder;

class ImageSearchService
{
    protected const MAX_RETRIES = 1;

    protected array $providers;

    protected ?array $payload;

    public function __construct()
    {
        $this->setupProviders();
    }

    protected function setupProviders()
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

        // Filter providers to only those for which credentials are provided
        $this->providers = array_filter($this->providers, function ($_, $provider) {
            return isset($this->payload['credentials'][$provider]) && filled($this->payload['credentials'][$provider]);
        }, ARRAY_FILTER_USE_BOTH);

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

    public function resultLimit(): ?int
    {
        return $this->payload['resultLimit'] > 0 ? $this->payload['resultLimit'] : null;
    }

    public function search(string $keyword): ?array
    {
        $providerKeys = array_keys($this->providers);

        if ($this->randomProvider()) {
            shuffle($providerKeys);
        }

        foreach ($providerKeys as $providerName) {
            try {
                $result = $this->searchWithProvider($providerName, $keyword);

                if (!$result instanceof SearchResponse || $result->isEmpty()) {
                    continue;
                }

                $images = $result->get();

                if ($this->resultLimit() > 0) {
                    $images = $images->take($this->resultLimit());
                }

                if ($this->randomResult()) {
                    $images = $images->shuffle();
                }

                return [
                    'images' => $images->toArray(),
                    'keyword_used' => $keyword,
                    'meta' => $result->meta(),
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function searchWithProvider(string $providerName, string $keyword): ?SearchResponse
    {
        $searchMethod = $this->providers[$providerName];

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return call_user_func($searchMethod, $keyword);
            } catch (\Exception $e) {
                if ($attempt === self::MAX_RETRIES) {
                    throw $e;
                }
                usleep(100000); // Retry delay
            }
        }

        return null;
    }

    protected function searchPexels(string $keyword): SearchResponse
    {
        return Pexels::make()
            ->cache($this->payload['cache'] === true)
            ->timeout($this->payload['timeout'] ?? 60)
            ->page($this->payload['page'] ?? 1)
            ->apiKey($this->payload['credentials']['pexels'])
            ->cachedOrQuery($keyword);
    }

    protected function searchUnsplash(string $keyword): SearchResponse
    {
        return Unsplash::make()
            ->cache($this->payload['cache'] === true)
            ->timeout($this->payload['timeout'] ?? 60)
            ->page($this->payload['page'] ?? 1)
            ->apiKey($this->payload['credentials']['unsplash'])
            ->cachedOrQuery($keyword);
    }

    protected function searchPixabay(string $keyword): SearchResponse
    {
        return Pixabay::make()
            ->cache($this->payload['cache'] === true)
            ->timeout($this->payload['timeout'] ?? 60)
            ->page($this->payload['page'] ?? 1)
            ->apiKey($this->payload['credentials']['pixabay'])
            ->cachedOrQuery($keyword);
    }

    public function getFallbackImage(string $keyword): array
    {
        return Placeholder::make()
            ->size($this->payload['placeholderSize'] ?? '600x400')
            ->text($this->payload['placeholderText'] ?? 'Sample Text')
            ->bgColor($this->payload['placeholderBgColor'] ?? '#cccccc')
            ->textColor($this->payload['placeholderTextColor'] ?? '#000000')
            ->generate($keyword)
            ->toArray();
    }
}
