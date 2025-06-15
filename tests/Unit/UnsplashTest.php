<?php

use Illuminate\Support\Facades\Http;
use Apsonex\LaravelStockImage\Services\Providers\Unsplash;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

beforeEach(function () {
    Http::preventStrayRequests();
});

function mockUnsplash(array $overrides = []): Unsplash
{
    $unsplash = new Unsplash();
    $unsplash->apiKey($overrides['apiKey'] ?? 'dummy-key');
    $unsplash->page($overrides['page'] ?? 1);
    $unsplash->timeout($overrides['timeout'] ?? 5);
    $unsplash->cache(false);

    return $unsplash;
}

describe('unsplash_test', function () {
    test('unsplash_returns_empty_array_on_failed_response', function () {
        Http::fake([
            'https://api.unsplash.com/search/photos*' => Http::response([], 500),
        ]);

        $provider = mockUnsplash();
        $result = $provider->query('mountain');

        expect($result['items'])->toBeArray()->toBeEmpty();
    });

    test('unsplash_returns_empty_array_on_empty_results', function () {
        Http::fake([
            'https://api.unsplash.com/search/photos*' => Http::response(['results' => []], 200),
        ]);

        $provider = mockUnsplash();
        $result = $provider->query('lake');

        expect($result['items'])->toBeArray()->toBeEmpty();
    });

    test('unsplash_returns_normalized_photo_data_from_query', function () {
        Http::fake([
            'https://api.unsplash.com/search/photos*' => Http::response([
                'results' => [
                    [
                        'description' => 'A mountain peak',
                        'urls' => ['regular' => 'https://unsplash.com/image.jpg'],
                    ],
                ],
            ], 200),
        ]);

        $provider = mockUnsplash();
        $result = $provider->query('mountain');

        expect($result['items'])->toBeArray();
        expect($result['items'][0])->toMatchArray([
            'image_url' => 'https://unsplash.com/image.jpg',
            'image_description' => 'A mountain peak',
            'source' => 'unsplash',
        ]);
    });

    test('unsplash_search_returns_a_search_response_instance', function () {
        Http::fake([
            'https://api.unsplash.com/search/photos*' => Http::response([
                'results' => [
                    [
                        'description' => 'A dog on grass',
                        'urls' => ['regular' => 'https://unsplash.com/dog.jpg'],
                    ],
                ],
            ], 200),
        ]);

        $provider = mockUnsplash([
            'page' => 3,
            'timeout' => 10,
        ]);

        $response = $provider->search('dog');

        expect($response)->toBeInstanceOf(SearchResponse::class);
        expect($response->get())->toHaveCount(1);
        expect($response->meta())->toMatchArray([
            'page' => 3,
            'per_page' => $provider->maxPerPage(),
            'count' => 1,
        ]);
    });
});
