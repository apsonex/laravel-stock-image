<?php

namespace Apsonex\LaravelStockImage\Services\DTO;

class ImageDetail
{
    public function __construct(
        public string $image_url,
        public string $image_description,
        public string $source = 'pixabay'
    ) {}

    public static function from(array $data): static
    {
        // dd($data);
        return new static(
            image_url: $data['image_url'],
            image_description: $data['image_description'] ?? '',
            source: $data['source'] ?? 'unknown',
        );
    }

    public function toArray(): array
    {
        return [
            'image_url'         => $this->image_url,
            'image_description' => $this->image_description,
            'source'            => $this->source,
        ];
    }
}
