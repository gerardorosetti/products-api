<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Product\CreateProductAction;
use App\Actions\Product\DeleteProductAction;
use App\Actions\Product\ListProductsAction;
use App\Actions\Product\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class ProductController extends Controller
{
    /**
     * List products
     *
     * Paginated, filterable by search text, currency and price range, and sortable.
     */
    public function index(ProductIndexRequest $request, ListProductsAction $action): ProductCollection
    {
        return new ProductCollection($action->handle($request->validated()));
    }

    /**
     * Show a product
     */
    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load(['currency', 'prices.currency']));
    }

    /**
     * Create a product
     *
     * Optionally creates additional prices in other currencies in the same transaction.
     */
    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->handle($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /**
     * Update a product
     *
     * PUT replaces every field; PATCH updates only the fields sent.
     */
    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): ProductResource
    {
        return new ProductResource($action->handle($product, $request->validated()));
    }

    /**
     * Delete a product
     *
     * Soft delete: the product disappears from the API but the row is kept.
     */
    public function destroy(Product $product, DeleteProductAction $action): Response
    {
        Gate::authorize('delete', $product);

        $action->handle($product);

        return response()->noContent();
    }
}
