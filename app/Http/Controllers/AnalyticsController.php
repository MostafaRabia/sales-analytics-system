<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Services\AnalyticService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        public AnalyticService $analyticService
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return Response::apiResponse(
            HttpStatus::OK,
            $this->analyticService->getAnalytics(),
        );
    }
}
