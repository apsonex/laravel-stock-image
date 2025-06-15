<?php

namespace Apsonex\LaravelStockImage\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Apsonex\LaravelStockImage\Services\DTO\ImageDetail;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

class Unsplash extends BaseProvider
{
    public function maxPerPage(): int
    {
        return 30;
    }

    public function search(string $keyword): SearchResponse
    {
        $rawData = $this->query($keyword);

        return SearchResponse::fromCollection(collect($rawData['items'])->map(fn($d) => ImageDetail::from($d)), $this->page, $this->resolvePerPage());
    }

    public function query($keyword): array
    {
        $perPage = $this->resolvePerPage();

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => "Client-ID {$this->apiKey}",
            ])
            ->get('https://api.unsplash.com/search/photos', [
                'query'    => $keyword,
                'per_page' => $perPage,
                'page'     => $this->page,
            ]);

        if ($response->failed()) {
            return $this->itemsToArray();
        }

        $data = $response->json();

        if (empty($data['results'])) {
            return $this->itemsToArray();
        }

        $collection = collect($data['results'])->map(fn($photo) => $this->normalizePhoto($photo));

        return $this->itemsToArray($collection->toArray());
    }

    protected function normalizePhoto(array $photo): array
    {
        return array_filter([
            'image_url'         => Arr::get($photo, 'urls.regular') ?? Arr::get($photo, 'urls.full'),
            'image_description' => Arr::get($photo, 'description') ?? 'Unsplash Image',
            'source'            => 'unsplash',
        ]);
    }
}
