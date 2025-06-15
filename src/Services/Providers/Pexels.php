<?php

namespace Apsonex\LaravelStockImage\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Apsonex\LaravelStockImage\Services\DTO\ImageDetail;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

class Pexels extends BaseProvider
{
    public function maxPerPage(): int
    {
        return 80;
    }

    public function search(string $keyword): SearchResponse
    {
        $rawData = $this->query($keyword);

        return SearchResponse::fromCollection(
            collect($rawData['items'])->map(fn($photo) => ImageDetail::from($photo)),
            $this->page,
            $this->resolvePerPage()
        );
    }

    public function query($keyword): array
    {
        $perPage = $this->resolvePerPage();

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => $this->apiKey,
            ])
            ->get('https://api.pexels.com/v1/search', [
                'query'    => $keyword,
                'per_page' => $perPage,
                'page'     => $this->page,
            ]);

        if ($response->failed()) {
            return $this->itemsToArray();
        }

        $data = $response->json();

        if (empty($data['photos'])) {
            return $this->itemsToArray();
        }

        $collection = collect($data['photos'])->map(fn($photo) => $this->normalizePhoto($photo));

        return $this->itemsToArray($collection->toArray());
    }

    protected function normalizePhoto(array $photo): array
    {
        return array_filter([
            'image_url' => Arr::get($photo, 'src.large') ?? Arr::get($photo, 'src.original'),
            'image_description' => Arr::get($photo, 'alt'),
            'source' => 'pexels',
        ]);
    }
}
