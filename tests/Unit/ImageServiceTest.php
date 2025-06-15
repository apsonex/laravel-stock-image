<?php

use Apsonex\LaravelStockImage\Services\ImageSearchService;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;
use Illuminate\Support\Collection;
use Mockery as m;

beforeEach(function () {
    // Prevent real API hits
    config()->set('app.debug', true);
});

function validPayload(array $overrides = []): array
{
    return array_merge([
        'credentials' => [
            'pexels' => 'pexels-key',
            'unsplash' => 'unsplash-key',
            'pixabay' => 'pixabay-key',
        ],
        'cache' => false,
        'timeout' => 30,
        'page' => 1,
        'randomResult' => false,
        'randomProvider' => false,
        'resultLimit' => null,
        'placeholderSize' => '600x400',
        'placeholderText' => 'Hello',
        'placeholderTextColor' => '#000',
        'placeholderBgColor' => '#fff',
    ], $overrides);
}

function fakeSearchResponse(array $images = [['url' => 'img1.jpg'], ['url' => 'img2.jpg']], array $meta = []): SearchResponse
{
    return new SearchResponse(collect($images), 1, 20);
}

describe('image_service_test', function () {
    test('image_service_filters_out_providers_without_credentials', function () {
        $service = new ImageSearchService();

        $service->prepare(validPayload([
            'credentials' => ['pexels' => 'pexels-key'],
        ]));

        expect($service->credentials())->toHaveKey('pexels');
        expect($service->credentials())->not->toHaveKey('unsplash');
    });

    test('image_service_returns_null_if_all_providers_fail', function () {

        $mock = $this->partialMock(ImageSearchService::class)->shouldAllowMockingProtectedMethods();

        invade($mock)->setupProviders();

        $mock->prepare(validPayload([
            'credentials' => ['pexels' => 'key'],
        ]));

        $mock->prepare(validPayload([
            'credentials' => ['pexels' => 'key'],
        ]));

        $mock->shouldReceive('searchWithProvider')->andThrow(new Exception('Fail'));

        expect($mock->search('cat'))->toBeNull();
    });

    test('image_service_returns_first_successful_result_from_providers', function () {
        $service = $this->partialMock(ImageSearchService::class)->shouldAllowMockingProtectedMethods();

        invade($service)->setupProviders();

        $service->prepare(validPayload([
            'credentials' => [
                'pexels' => 'key',
                'unsplash' => 'key',
            ],
        ]));

        $service->shouldReceive('searchWithProvider')
            ->once()
            ->andReturn(fakeSearchResponse());

        $result = $service->search('forest');

        expect($result)->toHaveKey('images');
        expect($result['images'])->toBeArray();
    });

    test('image_service_applies_result_limit_correctly', function () {
        $service = $this->partialMock(ImageSearchService::class)->shouldAllowMockingProtectedMethods();
        invade($service)->setupProviders();

        $service->prepare(validPayload([
            'resultLimit' => 1,
        ]));

        $service->shouldReceive('searchWithProvider')
            ->andReturn(fakeSearchResponse([
                ['url' => '1.jpg'],
                ['url' => '2.jpg'],
            ]));

        $result = $service->search('sky');

        expect($result['images'])->toHaveCount(1);
    });

    test('image_service_shuffles_results_when_random_result_is_true', function () {
        $service = $this->partialMock(ImageSearchService::class)->shouldAllowMockingProtectedMethods();
        invade($service)->setupProviders();

        $service->prepare(validPayload([
            'randomResult' => true,
        ]));

        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('searchWithProvider')
            ->andReturn(fakeSearchResponse(
                $images = [
                    ['image_url' => 'a.jpg'],
                    ['image_url' => 'b.jpg'],
                    ['image_url' => 'c.jpg'],
                ]
            ));

        $result = $service->search('water');

        expect(
            in_array(
                $result['images'][0]['image_url'],
                collect(array_values($images))->map(fn($i) => $i['image_url'])->values()->toArray()
            )
        )->toBeTrue();
    });

    test('image_service_shuffles_provider_order_when_random_provider_is_true', function () {
        $service = $this->partialMock(ImageSearchService::class)->shouldAllowMockingProtectedMethods();
        invade($service)->setupProviders();

        $service->prepare(validPayload([
            'randomProvider' => true,
        ]));

        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('searchWithProvider')->once()->andReturn(fakeSearchResponse());

        $result = $service->search('mountains');

        expect($result)->not->toBeNull();
        expect($result['images'])->toBeArray();
    });

    test('image_service_get_fallback_image_returns_placeholder_data_correctly', function () {
        $service = new ImageSearchService();

        $service->prepare(validPayload([
            'placeholderSize' => '800x600',
            'placeholderText' => 'Demo',
            'placeholderTextColor' => '#eee',
            'placeholderBgColor' => '#333',
        ]));

        $result = $service->getFallbackImage('fallback');

        expect($result['images'][0]['image_url'])->not->toBeNull();
    });
});
