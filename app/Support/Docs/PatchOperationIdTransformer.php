<?php

declare(strict_types=1);

namespace App\Support\Docs;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;

/**
 * PUT|PATCH routes are documented as two operations; both would otherwise get
 * generated numeric suffixes. Use the route name for PUT and "<name>.patch" for PATCH.
 */
final class PatchOperationIdTransformer implements OperationTransformer
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $name = $routeInfo->route->getName();
        $methods = array_map('strtolower', $routeInfo->route->methods());

        if ($name === null || ! in_array('put', $methods, true) || ! in_array('patch', $methods, true)) {
            return;
        }

        $operation->setOperationId($routeInfo->method === 'patch' ? $name.'.patch' : $name);
    }
}
