<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Response::apiResponse(
            HttpStatus::OK,
            ProductResource::collection(
                Product::paginate()
                    ->withQueryString()
            )->resource, // To return pagination data
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        return Response::apiResponse(
            HttpStatus::CREATED,
            new ProductResource(Product::create($request->validated())),
            'Product created successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return Response::apiResponse(
            HttpStatus::OK,
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        return Response::apiResponse(
            HttpStatus::OK,
            new ProductResource(tap($product, fn (Product $product) => $product->update($request->validated()))),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        return Response::apiResponse(
            HttpStatus::OK,
            new ProductResource(tap($product, fn (Product $product) => $product->delete())),
            'Product deleted successfully'
        );
    }
}
