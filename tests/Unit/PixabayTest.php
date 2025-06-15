<?php

use Illuminate\Support\Facades\Http;
use Apsonex\LaravelStockImage\Services\Providers\Pixabay;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

beforeEach(function () {
    Http::preventStrayRequests();
});

function mockPixabay(array $overrides = []): Pixabay
{
    $pixabay = new Pixabay();
    $pixabay->apiKey($overrides['apiKey'] ?? 'dummy-key');
    $pixabay->page($overrides['page'] ?? 1);
    $pixabay->timeout($overrides['timeout'] ?? 5);
    $pixabay->cache(false);

    return $pixabay;
}

describe('pixabay_test', function () {
    test('pixabay_returns_empty_array_on_failed_response', function () {
        Http::fake([
            'https://pixabay.com/api*' => Http::response([], 500),
        ]);

        $provider = mockPixabay();
        $result = $provider->query('sunset');

        expect($result['items'])->toBeArray()->toBeEmpty();
    });

    test('pixabay_returns_empty_array_on_empty_hits', function () {
        Http::fake([
            'https://pixabay.com/api*' => Http::response(['hits' => []], 200),
        ]);

        $provider = mockPixabay();
        $result = $provider->query('forest');

        expect($result['items'])->toBeArray()->toBeEmpty();
    });

    test('pixabay_returns_normalized_photo_data_from_query', function () {
        Http::fake([
            'https://pixabay.com/api*' => Http::response([
                'hits' => [
                    [
                        'tags' => 'sunset, sky',
                        'largeImageURL' => 'https://pixabay.com/photo.jpg',
                    ],
                ],
            ], 200),
        ]);

        $provider = mockPixabay();
        $result = $provider->query('sunset');

        expect($result['items'])->toBeArray();
        expect($result['items'][0])->toMatchArray([
            'image_url' => 'https://pixabay.com/photo.jpg',
            'image_description' => 'sunset, sky',
            'source' => 'pixabay',
        ]);
    });

    test('pixabay_search_returns_a_search_response_instance', function () {
        Http::fake([
            'https://pixabay.com/api*' => Http::response([
                'hits' => [
                    [
                        'tags' => 'beach, ocean',
                        'largeImageURL' => 'https://pixabay.com/beach.jpg',
                    ],
                ],
            ], 200),
        ]);

        $provider = mockPixabay([
            'page' => 4,
            'timeout' => 10,
        ]);

        $response = $provider->search('beach');

        expect($response)->toBeInstanceOf(SearchResponse::class);
        expect($response->get())->toHaveCount(1);
        expect($response->meta())->toMatchArray([
            'page' => 4,
            'per_page' => $provider->maxPerPage(),
            'count' => 1,
        ]);
    });
});
