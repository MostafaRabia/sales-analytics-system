<?php

namespace App\Providers;

use App\Enums\HttpStatus;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Response::macro('apiResponse', function (HttpStatus $status, array|object|null $data = null, ?string $message = null) {
            return Response::json([
                'http_code' => $status,
                'message' => $message ?: $status->message(),
                'data' => $data,
            ], $status->value);
        });
    }
}
