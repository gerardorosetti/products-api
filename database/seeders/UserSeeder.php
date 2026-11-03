<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@example.com', 'name' => 'Admin User', 'role' => Role::Admin],
            ['email' => 'viewer@example.com', 'name' => 'Viewer User', 'role' => Role::Viewer],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => (string) config('seeding.user_password')],
            );

            $user->syncRoles($data['role']->value);
        }
    }
}
