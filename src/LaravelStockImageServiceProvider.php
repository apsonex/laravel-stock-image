<?php

namespace Apsonex\LaravelStockImage;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Apsonex\LaravelStockImage\Commands\LaravelStockImageCommand;

class LaravelStockImageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-stock-image')
            ->hasRoute('web');
    }
}
