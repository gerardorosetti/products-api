<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;

final class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ProductsView->value);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo(Permission::ProductsView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ProductsCreate->value);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo(Permission::ProductsUpdate->value);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo(Permission::ProductsDelete->value);
    }

    public function addPrice(User $user, Product $product): bool
    {
        return $user->hasPermissionTo(Permission::PricesCreate->value);
    }
}
