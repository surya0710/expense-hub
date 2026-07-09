<?php

namespace App\Providers;

use App\Support\Storage\ReceiptStorageManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReceiptStorageManager::class);
    }

    public function boot(): void
    {
        $this->registerReceiptDisks();
    }

    protected function registerReceiptDisks(): void
    {
        Config::set('filesystems.disks.'.config('receipts.local_disk'), [
            'driver' => 'local',
            'root' => storage_path('app/receipts'),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ]);

        if ($this->s3CredentialsPresent()) {
            Config::set('filesystems.disks.'.config('receipts.s3_disk'), [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket' => env('AWS_BUCKET'),
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'root' => env('AWS_RECEIPTS_ROOT', 'expensehub'),
                'visibility' => 'private',
                'throw' => false,
                'report' => false,
            ]);
        }
    }

    protected function s3CredentialsPresent(): bool
    {
        return filled(env('AWS_ACCESS_KEY_ID'))
            && filled(env('AWS_SECRET_ACCESS_KEY'))
            && filled(env('AWS_BUCKET'));
    }
}
