<?php

namespace Apsonex\LaravelStockImage\Services\Providers;

use Apsonex\LaravelStockImage\Concerns\Makeable;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Apsonex\LaravelStockImage\Services\DTO\ImageDetail;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

abstract class BaseProvider
{
    use Makeable;

    protected int $timeout = 60;
    protected int $page = 1;
    protected ?int $perPage = null;
    protected string $apiKey;
    protected bool $cache = false;

    public function apiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function page(int $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function timeout(int $timeout = 60): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function cache(bool $cache): static
    {
        $this->cache = $cache;
        return $this;
    }

    public function cachedOrQuery(string $keyword): SearchResponse
    {
        if ($this->cache) {
            $cacheKey = $this->getCacheKey($keyword);
            $rawData = Cache::remember($cacheKey, now()->addDay(), fn() => $this->query($keyword));
        } else {
            $rawData = $this->query($keyword);
        }

        return SearchResponse::fromCollection(collect($rawData['items']), $rawData['page'], $rawData['perPage']);
    }

    protected function getCacheKey(string $keyword): string
    {
        return 'stock-image:' . str(static::class)->afterLast('\\')->kebab() . ':' . Str::slug($keyword) . ":page{$this->page}";
    }


    protected function resolvePerPage(): int
    {
        return ($this->perPage && $this->perPage > $this->maxPerPage())
            ? $this->maxPerPage()
            : ($this->perPage ?: $this->maxPerPage());
    }

    protected function itemsToArray($items = []): array
    {
        return [
            'items' => $items,
            'page' => $this->page,
            'perPage' => $this->resolvePerPage(),
        ];
    }

    /**
     * Fetch collection of arrays only (not full DTO), to cache
     */
    abstract protected function query(string $keyword): array;

    /**
     * For direct search without cache
     */
    abstract public function search(string $keyword): SearchResponse;

    abstract public function maxPerPage(): int;
}
