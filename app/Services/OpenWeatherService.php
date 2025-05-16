<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenWeatherService
{
    private static $client;

    public function __construct()
    {
        if (!isset(self::$client)) {
            self::$client = Http::baseUrl('https://api.openweathermap.org/')->acceptJson();
        }
    }

    public function current(string $city): array
    {
        if (Cache::has('open_weather_' . $city)) {
            return Cache::get('open_weather_' . $city);
        }

        $response = self::$client->get('data/2.5/weather', [
            'q' => $city,
            'appid' => config('open_weather.api_key'),
            'units' => 'metric',
        ]);

        if ($response->failed()) {
            throw new \Exception('OpenWeather API request failed: ' . $response->body());
        }

        Cache::put('open_weather_' . $city, $response->json(), 3600); // Cache for 1 hour

        return $response->json();
    }
}
