<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case CurrenciesView = 'currencies.view';
    case CurrenciesManage = 'currencies.manage';
    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';
    case PricesCreate = 'prices.create';
}
