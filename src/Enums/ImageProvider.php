<?php

namespace Apsonex\LaravelStockImage\Enums;

enum ImageProvider: string
{
    case Pexel = 'pexels';
    case Unsplash = 'unsplash';
    case Pixabay = 'pixabay';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
