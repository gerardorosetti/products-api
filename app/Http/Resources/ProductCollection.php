<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

final class ProductCollection extends ResourceCollection
{
    /**
     * @var string
     */
    public $collects = ProductResource::class;
}
