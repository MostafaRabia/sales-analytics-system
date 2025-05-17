<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\AnalyticService;
use App\Services\BroadcastService;
use Illuminate\Support\Facades\Response;

class OrderController extends Controller
{
    public function __construct(
        public BroadcastService $broadcastService,
        public AnalyticService $analyticService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Response::apiResponse(
            HttpStatus::OK,
            OrderResource::collection(
                Order::with('product')
                    ->paginate()
                    ->withQueryString()
            )->resource, // To return pagination data
            'Orders retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrderRequest $request)
    {
        $order = Order::create($request->validated());

        $this->broadcastService->sendMessage([
            'type' => 'new_order',
            'data' => [
                'order' => $order,
            ],
        ]);

        $this->broadcastService->sendMessage([
            'type' => 'update_analytics',
            'data' => $this->analyticService->getAnalytics(),
        ]);

        return Response::apiResponse(
            HttpStatus::CREATED,
            new OrderResource($order),
            'Order created successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        return Response::apiResponse(
            HttpStatus::OK,
            new OrderResource($order->load('product')),
            'Order retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrderRequest $request, Order $order)
    {
        return Response::apiResponse(
            HttpStatus::OK,
            new OrderResource(tap($order, fn (Order $order) => $order->update($request->validated()))),
            'Order updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        return Response::apiResponse(
            HttpStatus::NO_CONTENT,
        );
    }
}
