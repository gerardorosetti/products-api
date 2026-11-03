<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksPermission;

final class ProductPolicy
{
    use ChecksPermission;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::ProductsView);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->allows($user, Permission::ProductsView);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::ProductsCreate);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->allows($user, Permission::ProductsUpdate);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->allows($user, Permission::ProductsDelete);
    }

    public function addPrice(User $user, Product $product): bool
    {
        return $this->allows($user, Permission::PricesCreate);
    }
}
