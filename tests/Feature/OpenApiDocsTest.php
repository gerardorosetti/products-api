<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->spec = $this->getJson('/docs/api.json')->assertOk()->json();
});

it('documents PATCH next to PUT for updatable resources', function (string $path): void {
    expect(array_keys($this->spec['paths'][$path]))->toContain('put', 'patch');
})->with(['/products/{product}', '/currencies/{currency}']);

it('documents unique operation ids', function (): void {
    $ids = [];
    foreach ($this->spec['paths'] as $operations) {
        foreach ($operations as $operation) {
            $ids[] = $operation['operationId'];
        }
    }

    expect($ids)->toBe(array_values(array_unique($ids)));
});

it('documents the login user as an object', function (): void {
    $data = $this->spec['paths']['/auth/login']['post']['responses']['200']['content']['application/json']['schema']['properties']['data'];
    $login = $this->spec['components']['schemas'][basename($data['$ref'])];

    expect($login['properties']['user']['$ref'])->toEndWith('/UserResource');
    expect($this->spec['components']['schemas']['UserResource']['type'])->toBe('object');
});

it('documents the 409 response of a currency delete', function (): void {
    $response = $this->spec['paths']['/currencies/{currency}']['delete']['responses']['409'];

    expect(json_encode($response))->toContain('currency_in_use')->toContain('conflict');
});

it('documents amounts with their real limits and no rounded maximum', function (): void {
    $price = $this->spec['components']['schemas']['StoreProductRequest']['properties']['price'];

    expect($price)->not->toHaveKey('maximum')
        ->and($price['description'])->toContain('999999999999999.99');
});
