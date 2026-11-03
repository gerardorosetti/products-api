<?php

declare(strict_types=1);

it('prunes expired Sanctum tokens daily', function (): void {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('sanctum:prune-expired --hours=24')
        ->assertSuccessful();
});
