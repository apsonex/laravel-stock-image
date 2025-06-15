<?php

namespace Apsonex\LaravelStockImage\Concerns;

trait Makeable
{
    public static function make(): static
    {
        return new static;
    }
}
