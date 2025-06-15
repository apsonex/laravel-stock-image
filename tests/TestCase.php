<?php

namespace Apsonex\LaravelStockImage\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Apsonex\LaravelStockImage\LaravelStockImageServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Apsonex\\LaravelStockImage\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelStockImageServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $config = require(__DIR__ . '/../config/stock-image.php');

        config()->set('database.default', 'testing');
        config()->set('stock-image', $config);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
