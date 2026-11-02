<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;

arch()->preset()->php();

arch()->preset()->security();

arch('app classes use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging statements')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('actions are final and readonly')
    ->expect('App\Actions')
    ->toBeFinal()
    ->toBeReadonly();

arch('policies and form requests are final')
    ->expect(['App\Policies', 'App\Http\Requests'])
    ->toBeFinal();

it('does not cast model attributes to float', function (): void {
    foreach ([Currency::class, Product::class, ProductPrice::class, User::class] as $model) {
        expect((new $model)->getCasts())->not->toContain('float')->not->toContain('double');
    }
});

arch('controllers do not use the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');
