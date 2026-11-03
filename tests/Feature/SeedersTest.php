<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

it('is idempotent', function (): void {
    $snapshot = fn (): array => [
        PermissionModel::count(),
        RoleModel::count(),
        User::count(),
        Currency::count(),
        Product::count(),
        ProductPrice::count(),
    ];

    $this->seed(DatabaseSeeder::class);
    $counts = $snapshot();

    $this->seed(DatabaseSeeder::class);

    expect($snapshot())->toBe($counts)
        ->and($counts[0])->toBe(count(Permission::cases()))
        ->and($counts[1])->toBe(count(Role::cases()))
        ->and($counts[2])->toBe(2)
        ->and($counts[3])->toBe(8)
        ->and($counts[4])->toBe(30);
});

it('seeds products with one to three prices in other currencies', function (): void {
    $this->seed(DatabaseSeeder::class);

    Product::query()->with('prices')->get()->each(function (Product $product): void {
        expect($product->prices->count())->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(3)
            ->and($product->prices->pluck('currency_id'))->not->toContain($product->currency_id);
    });
});

it('seeds USD with a rate of one', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Currency::query()->where('code', 'USD')->firstOrFail()->exchange_rate)->toBe('1.00000000');
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
