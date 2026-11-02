<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

it('is idempotent', function (): void {
    $this->seed(DatabaseSeeder::class);
    $counts = [PermissionModel::count(), RoleModel::count(), User::count()];

    $this->seed(DatabaseSeeder::class);

    expect([PermissionModel::count(), RoleModel::count(), User::count()])->toBe($counts)
        ->and($counts)->toBe([count(Permission::cases()), count(Role::cases()), 2]);
});

it('gives the admin every permission', function (): void {
    $this->seed(DatabaseSeeder::class);
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    foreach (Permission::cases() as $permission) {
        expect($admin->hasPermissionTo($permission->value))->toBeTrue();
    }

    expect($admin->name)->toBe('Admin User')
        ->and($admin->hasRole(Role::Admin->value))->toBeTrue();
});

it('gives the viewer only view permissions', function (): void {
    $this->seed(DatabaseSeeder::class);
    $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();

    expect($viewer->name)->toBe('Viewer User')
        ->and($viewer->getAllPermissions()->pluck('name')->all())
        ->toEqualCanonicalizing(['products.view', 'currencies.view']);
});

it('removes stale permissions from a role when re-seeded', function (): void {
    $this->seed(DatabaseSeeder::class);
    RoleModel::findByName(Role::Viewer->value, 'web')->givePermissionTo(Permission::ProductsDelete->value);

    $this->seed(DatabaseSeeder::class);

    expect(RoleModel::findByName(Role::Viewer->value, 'web')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing(['products.view', 'currencies.view']);
});

it('uses the configured seed password', function (): void {
    config(['seeding.user_password' => 'Another-Secret-1']);
    $this->seed(DatabaseSeeder::class);

    $this->postJson(route('auth.login'), ['email' => 'admin@example.com', 'password' => 'Another-Secret-1'])
        ->assertOk();
});
