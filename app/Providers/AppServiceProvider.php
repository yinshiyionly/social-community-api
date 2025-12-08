<?php

namespace App\Providers;

use App\Services\Filesystem\VolcengineFilesystemAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Tos\TosClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('volcengine', function ($app, $config) {
            $client = new TosClient([
                'access_key_id' => $config['key'],
                'access_key_secret' => $config['secret'],
                'endpoint' => $config['endpoint'],
                'region' => $config['region'],
            ]);
            
            $adapter = new VolcengineFilesystemAdapter($client, $config['bucket'], $config);
            
            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
