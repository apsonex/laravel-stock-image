<?php

use Illuminate\Support\Facades\Http;
use Apsonex\LaravelStockImage\Services\Providers\Pexels;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

beforeEach(function () {
    Http::preventStrayRequests();
});

function mockPexels(array $overrides = []): Pexels
{
    $pexels = new Pexels();
    $pexels->apiKey($overrides['apiKey'] ?? 'dummy-key');
    $pexels->page($overrides['page'] ?? 1);
    $pexels->timeout($overrides['timeout'] ?? 5);
    $pexels->cache(false);

    return $pexels;
}

describe('pexels_test', function () {
    test('pexel_test_returns_empty_array_on_failed_response', function () {
        Http::fake([
            'https://api.pexels.com/v1/search*' => Http::response([], 500),
        ]);

        $provider = mockPexels();
        $result = $provider->query('cat');

        expect($result['items'])->toBeArray()->toBeEmpty();
    });

    test('pexel_test_returns_empty_array_on_empty_photos', function () {
        Http::fake([
            'https://api.pexels.com/v1/search*' => Http::response(['photos' => []], 200),
        ]);

        $provider = mockPexels();
        $result = $provider->query('cat');

        expect($result['items'])->toBeArray()->toBeEmpty();
    });

    test('pexel_test_returns_normalized_photo_data_from_query', function () {
        Http::fake([
            'https://api.pexels.com/v1/search*' => Http::response([
                'photos' => [
                    [
                        'alt' => 'A cute kitten',
                        'src' => ['large' => 'https://example.com/image.jpg'],
                    ],
                ],
            ], 200),
        ]);

        $provider = mockPexels();
        $result = $provider->query('kitten');

        expect($result['items'])->toBeArray();
        expect($result['items'][0])->toMatchArray([
            'image_url' => 'https://example.com/image.jpg',
            'image_description' => 'A cute kitten',
            'source' => 'pexels',
        ]);
    });

    test('pexel_test_search_returns_a_search_response_instance', function () {
        Http::fake([
            'https://api.pexels.com/v1/search*' => Http::response([
                'photos' => [
                    [
                        'alt' => 'A dog running',
                        'src' => ['large' => 'https://example.com/dog.jpg'],
                    ],
                ],
            ], 200),
        ]);

        $provider = mockPexels([
            'page' => 2,
            'timeout' => 10,
        ]);

        $response = $provider->search('dog');

        expect($response)->toBeInstanceOf(SearchResponse::class);
        expect($response->get())->toHaveCount(1);
        expect($response->meta())->toMatchArray([
            'page' => 2,
            'per_page' => $provider->maxPerPage(),
            'count' => 1,
        ]);
    });
});
