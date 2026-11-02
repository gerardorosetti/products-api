<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('app classes use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging statements')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('controllers do not use the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');
