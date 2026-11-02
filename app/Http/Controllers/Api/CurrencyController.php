<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Currency\CreateCurrencyAction;
use App\Actions\Currency\DeleteCurrencyAction;
use App\Actions\Currency\UpdateCurrencyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class CurrencyController extends Controller
{
    /**
     * List currencies
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Currency::class);

        return CurrencyResource::collection(Currency::query()->orderBy('code')->get());
    }

    /**
     * Show a currency
     */
    public function show(Currency $currency): CurrencyResource
    {
        Gate::authorize('view', $currency);

        return new CurrencyResource($currency);
    }

    /**
     * Create a currency
     */
    public function store(StoreCurrencyRequest $request, CreateCurrencyAction $action): JsonResponse
    {
        $currency = $action->handle($request->validated());

        return (new CurrencyResource($currency->refresh()))->response()->setStatusCode(201);
    }

    /**
     * Update a currency
     *
     * PUT replaces every field; PATCH updates only the fields sent.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency, UpdateCurrencyAction $action): CurrencyResource
    {
        return new CurrencyResource($action->handle($currency, $request->validated()));
    }

    /**
     * Delete a currency
     *
     * Fails with 409 when a product or price still references the currency.
     */
    public function destroy(Currency $currency, DeleteCurrencyAction $action): Response
    {
        Gate::authorize('delete', $currency);

        $action->handle($currency);

        return response()->noContent();
    }
}
