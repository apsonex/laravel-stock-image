<?php

namespace Apsonex\LaravelStockImage\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Apsonex\LaravelStockImage\Services\DTO\ImageDetail;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

class Pixabay extends BaseProvider
{
    public function maxPerPage(): int
    {
        return 200;
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
            ->get('https://pixabay.com/api/', [
                'key'        => $this->apiKey,
                'q'          => $keyword,
                'image_type' => 'photo',
                'per_page'   => $perPage,
                'page'       => $this->page,
                'safesearch' => 'true',
            ]);

        if ($response->failed()) {
            return $this->itemsToArray();
        }

        $data = $response->json();

        if (empty($data['hits'])) {
            return $this->itemsToArray();
        }

        $collection = collect($data['hits'])->map(fn($photo) => $this->normalizePhoto($photo));

        return $this->itemsToArray($collection->toArray());
    }

    protected function normalizePhoto(array $photo): array
    {
        return array_filter([
            'image_url'         => Arr::get($photo, 'largeImageURL') ?? Arr::get($photo, 'webformatURL'),
            'image_description' => Arr::get($photo, 'tags') ?? 'Pixabay Image',
            'source'            => 'pixabay',
        ]);
    }
}
