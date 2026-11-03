<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\User;

trait ChecksPermission
{
    /**
     * The user needs the permission and the current token must carry it as an ability.
     */
    private function allows(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo($permission->value) && $user->tokenCan($permission->value);
    }
}
