<?php

namespace Apsonex\LaravelStockImage\Services\Providers;

use Apsonex\LaravelStockImage\Concerns\Makeable;
use Apsonex\LaravelStockImage\Services\DTO\ImageDetail;
use Apsonex\LaravelStockImage\Services\DTO\SearchResponse;

class Placeholder
{
    use Makeable;

    protected ?string $size = null;
    protected ?string $text = null;
    protected ?string $bgColor = null;
    protected ?string $textColor = null;

    public function size(?string $size = null): static
    {
        $this->size = $size;
        return $this;
    }

    public function text(?string $text = null): static
    {
        $this->text = $text;
        return $this;
    }

    public function bgColor(?string $bgColor = null): static
    {
        $this->bgColor = $bgColor;
        return $this;
    }

    public function textColor(?string $textColor = null): static
    {
        $this->textColor = $textColor;
        return $this;
    }

    public function generate(string $keyword): SearchResponse
    {
        $placeholderUrls = $this->urls();
        $selected = $placeholderUrls[array_rand($placeholderUrls)];

        return $this->toResponse([
            'image_url'         => $selected,
            'image_description' => 'Placeholder image',
            'source'            => 'placeholder',
        ]);
    }

    protected function urls(): array
    {
        $size = $this->size ?: '600x400';
        $text = urlencode($this->text ?: 'Sample Image');
        $bgColor = ltrim($this->bgColor ?: 'cccccc', '#');
        $textColor = ltrim($this->textColor ?: '000000', '#');

        return [
            "https://placehold.co/{$size}?text={$text}",
            "https://dummyimage.com/{$size}/{$bgColor}/{$textColor}&text={$text}",
        ];
    }

    protected function toResponse(array $photoData): SearchResponse
    {
        $collection = collect([ImageDetail::from($photoData)]);
        return SearchResponse::fromCollection($collection, 1, 1);
    }
}
