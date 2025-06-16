<?php

use Apsonex\LaravelStockImage\Http\Controllers\ImageSearchController;
use Illuminate\Support\Facades\Route;

if (config('stock-image.route.enable') === true) {
    Route::middleware(config('stock-image.route.middleware') ?: [])->group(function () {

        /**
         * Search Stock Image
         */
        Route::post(
            config('stock-image.route.path'),
            [ImageSearchController::class, 'search']
        )->name('stock-image.search');
    });
}
