<?php

declare(strict_types=1);

it('returns the api name and docs url at the root', function (): void {
    $this->getJson('/')
        ->assertOk()
        ->assertExactJson([
            'name' => config('app.name'),
            'docs' => url('/docs/api'),
        ]);
});
