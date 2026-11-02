<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ProductPrice\AddProductPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductPrice\StoreProductPriceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ProductPriceController extends Controller
{
    /**
     * List the additional prices of a product
     */
    public function index(Product $product): AnonymousResourceCollection
    {
        Gate::authorize('view', $product);

        return ProductPriceResource::collection($product->load('prices.currency')->prices);
    }

    /**
     * Add a price to a product
     *
     * The currency must differ from the product base currency and be unique per product.
     */
    public function store(StoreProductPriceRequest $request, Product $product, AddProductPriceAction $action): JsonResponse
    {
        /** @var array{currency_id: int, price: string} $data */
        $data = $request->validated();

        return (new ProductPriceResource($action->handle($product, $data)))->response()->setStatusCode(201);
    }
}
