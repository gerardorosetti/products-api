<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Currency;
use App\Models\User;
use App\Policies\Concerns\ChecksPermission;

final class CurrencyPolicy
{
    use ChecksPermission;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::CurrenciesView);
    }

    public function view(User $user, Currency $currency): bool
    {
        return $this->allows($user, Permission::CurrenciesView);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::CurrenciesManage);
    }

    public function update(User $user, Currency $currency): bool
    {
        return $this->allows($user, Permission::CurrenciesManage);
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $this->allows($user, Permission::CurrenciesManage);
    }
}
