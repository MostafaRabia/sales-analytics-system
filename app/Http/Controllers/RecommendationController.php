<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Models\Product;
use App\Services\AnalyticService;
use App\Services\GeminiService;
use App\Services\OpenWeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class RecommendationController extends Controller
{
    public function __construct(
        public GeminiService $geminiService,
        public OpenWeatherService $openWeatherService,
        public AnalyticService $analyticService,
    ) {}

    public function revenue()
    {
        $recommendations = $this->geminiService->generateText('
            Given this data: '.json_encode($this->analyticService->getAnalytics()).'\n
            Which products should we promote for higher revenue?
        ');

        return Response::apiResponse(
            HttpStatus::OK,
            [
                'recommendations' => $recommendations,
            ],
        );
    }

    public function user(Request $request)
    {
        $recommendations = $this->geminiService->generateText('
            My weather is: ' . json_encode($this->openWeatherService->current($request->city)) . '\n
            My products are: ' . Product::take(50)->inRandomOrder()->get()->implode('name', ', ') . '\n
            Recommend which product I should buy based on the weather and my products.
        ');

        return Response::apiResponse(
            HttpStatus::OK,
            [
                'recommendations' => $recommendations,
            ],
        );
    }
}
