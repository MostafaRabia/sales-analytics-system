<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
Route::apiResource('orders', OrderController::class);

Route::get('analytics', \App\Http\Controllers\AnalyticsController::class);
Route::get('revenue-recommendations', [\App\Http\Controllers\RecommendationController::class, 'revenue']);
Route::get('user-recommendations', [\App\Http\Controllers\RecommendationController::class, 'user']);
