<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;

it('grants the admin role every permission', function (): void {
    expect(Role::Admin->permissions())->toBe(Permission::cases());
});

it('grants the viewer role only read permissions', function (): void {
    expect(Role::Viewer->permissions())->toBe([Permission::ProductsView, Permission::CurrenciesView]);
});

it('keeps permission values as dotted resource.action names', function (): void {
    foreach (Permission::cases() as $permission) {
        expect($permission->value)->toMatch('/^[a-z]+\.[a-z]+$/');
    }
});
