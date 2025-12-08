<?php

namespace App\Providers;

use App\Services\Filesystem\VolcengineFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Tos\TosClient;

class VolcengineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('volcengine', function ($app, $config) {
            // Create TOS client with array configuration
            $client = new TosClient([
                'region' => $config['region'],
                'ak' => $config['key'],
                'sk' => $config['secret'],
                'endpoint' => $config['endpoint'],
            ]);

            $adapter = new VolcengineFilesystemAdapter($client, $config['bucket'], $config);

            return new Filesystem($adapter);
        });
    }
}