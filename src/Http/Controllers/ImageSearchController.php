<?php
namespace Apsonex\LaravelStockImage\Http\Controllers;

use Apsonex\LaravelStockImage\Enums\ImageProvider;
use Apsonex\LaravelStockImage\Services\ImageSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * MCP-compliant Image Search Controller
 *
 * Handles POST /tools/image.search requests according to MCP Tool specification
 */
class ImageSearchController extends Controller
{
    const MAX_TIMEOUT = 120;

    public function __construct(
        protected readonly ImageSearchService $imageSearchService
    ) {}

    /**
     * Search for images based on provided keywords
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function search(Request $request): JsonResponse
    {
        // Validate MCP tool input schema
        $validator = Validator::make($request->all(), [
            'keywords'               => 'required|string|max:500',
            'random_result'          => 'boolean|nullable',
            'random_provider'        => 'boolean|nullable',
            'page'                   => 'nullable|integer',
            'cache'                  => 'nullable|boolean',
            'result_limit'           => 'nullable|integer',
            'provider_api_keys'      => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $allowedProviders = ImageProvider::values();

                    foreach ($value as $provider => $apiKey) {
                        if (! in_array($provider, $allowedProviders)) {
                            $fail("The provider '{$provider}' is not a valid image provider.");
                        }

                        if (empty($apiKey) || ! is_string($apiKey)) {
                            $fail("The API key for provider '{$provider}' must be a non-empty string.");
                        }
                    }
                },
            ],
            'placeholder_size'       => 'nullable|string|max:20',
            'placeholder_text'       => 'nullable|string|max:30',
            'placeholder_text_color' => 'nullable|string|max:10',
            'placeholder_bg_color'   => 'nullable|string||max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => 'error'], 422);
        }

        try {
            // $keywords       = ;
            $keywordArray = $this->parseKeywords($request->input('keywords'));
            $service      = $this->imageSearchService
                ->prepare([
                    'cache'                => $request->get('cache') === true,
                    'timeout'              => $request->get('request_timeout') > static::MAX_TIMEOUT ? static::MAX_TIMEOUT : $request->get('request_timeout'),
                    'page'                 => $request->get('page'),
                    'credentials'          => $request->get('provider_api_keys'),
                    'resultLimit'          => $request->get('result_limit'),
                    'randomResult'         => $request->get('random_result') === true,
                    'randomProvider'       => $request->get('random_provider') === true,
                    'placeholderSize'      => $request->get('placeholder_size'),
                    'placeholderText'      => $request->get('placeholder_text'),
                    'placeholderTextColor' => $request->get('placeholder_text_color'),
                    'placeholderBgColor'   => $request->get('placeholder_bg_color'),
                ]);

            // Search for first available image
            foreach ($keywordArray as $keyword) {
                $result = $service->search($keyword);

                if ($result) {
                    $this->printLog($result);

                    return response()->json([
                         ...$result,
                        'status' => 'success',
                    ]);
                }
            }

            // Fallback if no images found for any keyword
            $fallbackResult = $this->imageSearchService->getFallbackImage($keywordArray[0] ?? 'image');

            return response()->json([
                 ...$fallbackResult,
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Image search failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse comma-separated keywords into array
     *
     * @param string $keywords
     * @return array
     */
    private function parseKeywords(string $keywords): array
    {
        return array_filter(
            array_map('trim', explode(',', $keywords)),
            fn($keyword) => ! empty($keyword)
        );
    }

    protected function printLog($result)
    {
        Log::debug(implode("\n", [
            '---',
            'Output: ',
            now('America/Toronto')->toDayDateTimeString(),
            json_encode($result),
            '---',
        ]));
    }
}
