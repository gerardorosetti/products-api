<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function actingAsAdmin(): User
{
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    return $user;
}

function actingAsViewer(): User
{
    $user = User::factory()->viewer()->create();
    Sanctum::actingAs($user);

    return $user;
}
