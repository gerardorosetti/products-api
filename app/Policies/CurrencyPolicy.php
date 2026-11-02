<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Currency;
use App\Models\User;

final class CurrencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::CurrenciesView->value);
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo(Permission::CurrenciesView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::CurrenciesManage->value);
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo(Permission::CurrenciesManage->value);
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $user->hasPermissionTo(Permission::CurrenciesManage->value);
    }
}
