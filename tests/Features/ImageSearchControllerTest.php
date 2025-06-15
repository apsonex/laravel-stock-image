<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Apsonex\LaravelStockImage\Services\ImageSearchService;

beforeEach(function () {
    // Fake logging and HTTP requests for clean tests
    Log::spy();
    Http::fake();
    Event::fake();

    // Bind dummy route
    // Route::post(config('stock-image.route.path'), [ImageSearchController::class, 'search']);
});

describe('image_seacrh_controller_test', function () {

    test('image_search_controller_it_fails_validation_with_missing_required_fields', function () {
        $response = $this->postJson(config('stock-image.route.path'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['keywords']);
    });

    test('image_search_controller_it_fails_validation_with_invalid_provider_key', function () {
        $response = $this->postJson(config('stock-image.route.path'), [
            'keywords' => 'nature',
            'provider_api_keys' => [
                'invalid_provider' => 'some-key',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider_api_keys']);
    });

    test('image_search_controller_it_returns_success_with_fake_image_provider_result', function () {

        $mock = $this->mock(ImageSearchService::class);
        $mock->shouldReceive('prepare')->once()->andReturnSelf();
        $mock->shouldReceive('search')->andReturn([
            'images' => [
                ['image_url' => 'https://example.com/image.jpg']
            ],
            'keyword_used' => 'sunset',
            'meta' => ['source' => 'pexels'],
        ]);

        App::instance(ImageSearchService::class, $mock);

        $response = $this->postJson(config('stock-image.route.path'), [
            'keywords' => 'sunset',
            'provider_api_keys' => [
                'pexels' => 'fake-key',
            ],
        ]);

        $response->assertStatus(200);

        expect($response->json()['images'])->not->toBeEmpty();
    });

    test('image_search_controller_it_falls_back_to_placeholder_if_no_providers_return_results', function () {

        $response = $this->postJson(config('stock-image.route.path'), [
            'keywords' => 'ocean',
        ]);

        expect($response->json()['images'][0]['source'])->toBe('placeholder');
    });

    test('image_search_controller_it_returns_500_if_exception_occurs', function () {
        $mockService = Mockery::mock(ImageSearchService::class);
        $mockService->shouldReceive('prepare')->andThrow(new Exception('Something went wrong'));

        App::instance(ImageSearchService::class, $mockService);

        $response = $this->postJson(config('stock-image.route.path'), [
            'keywords' => 'city',
            'provider_api_keys' => [
                'pexels' => 'key',
            ],
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Image search failed',
            'message' => 'Something went wrong',
        ]);
    });
});
