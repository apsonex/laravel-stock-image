<?php

namespace Apsonex\LaravelStockImage\Services\DTO;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

class SearchResponse implements Arrayable
{
    /**
     * @param  Collection<ImageDetail>  $images
     */
    public function __construct(
        public Collection $images,
        public int $page = 1,
        public int $perPage = 20
    ) {}

    public static function fromCollection(Collection $images, int $page = 1, int $perPage = 20): static
    {
        return new static($images, $page, $perPage);
    }

    public function toArray(): array
    {
        return [
            'images' => $this->images->map(fn(ImageDetail $image) => $image->toArray())->all(),
            'meta' => $this->meta(),
        ];
    }

    public function isEmpty(): bool
    {
        return $this->images->isEmpty();
    }

    public function count(): int
    {
        return $this->images->count();
    }

    public function first(): ?ImageDetail
    {
        return $this->images->first();
    }

    public function shuffle(): Collection
    {
        return $this->images->shuffle();
    }

    public function get(): Collection
    {
        return $this->images;
    }

    public function meta(): array
    {
        return [
            'page' => $this->page,
            'per_page'     => $this->perPage,
            'count'        => $this->images->count(),
        ];
    }
}
