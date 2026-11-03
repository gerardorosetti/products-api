<?php

declare(strict_types=1);

use App\Actions\Currency\CreateCurrencyAction;
use App\Actions\Currency\DeleteCurrencyAction;
use App\Actions\Currency\UpdateCurrencyAction;
use App\Exceptions\CurrencyInUseException;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

it('maps a duplicate currency code on create to a validation error', function (): void {
    $existing = Currency::factory()->create(['code' => 'ABC']);

    expect(fn () => app(CreateCurrencyAction::class)->handle([
        'name' => 'Dup',
        'code' => $existing->code,
        'symbol' => '$',
        'exchange_rate' => '1',
    ]))->toThrow(function (ValidationException $e): void {
        expect($e->errors())->toHaveKey('code');
    });
});

it('maps a duplicate currency code on update to a validation error', function (): void {
    $first = Currency::factory()->create(['code' => 'ABC']);
    $second = Currency::factory()->create(['code' => 'XYZ']);

    expect(fn () => app(UpdateCurrencyAction::class)->handle($second, ['code' => $first->code]))
        ->toThrow(function (ValidationException $e): void {
            expect($e->errors())->toHaveKey('code');
        });
});

it('reports a currency referenced after the in-use check as in use', function (): void {
    $currency = Currency::factory()->create();
    $other = Currency::factory()->create();

    Currency::deleting(function () use ($currency, $other): void {
        Product::factory()->create(['currency_id' => $currency->id]);
        Product::factory()->create(['currency_id' => $other->id]);
    });

    expect(fn () => app(DeleteCurrencyAction::class)->handle($currency))->toThrow(CurrencyInUseException::class);
    expect(Currency::query()->whereKey($currency->id)->exists())->toBeTrue();
});

it('renders unexpected unique violations as a conflict envelope', function (): void {
    Route::get(apiPath('__unique'), fn () => throw new UniqueConstraintViolationException('pgsql', 'insert', [], new PDOException('duplicate key')));

    $this->getJson(apiPath('__unique'))
        ->assertConflict()
        ->assertExactJson(['message' => 'The request conflicts with the current state of the resource.', 'code' => 'conflict']);
});

it('renders unexpected foreign key violations as a conflict envelope', function (): void {
    Route::get(apiPath('__fk'), fn () => throw new QueryException('pgsql', 'delete', [], new PDOException('violates foreign key constraint')));

    $this->getJson(apiPath('__fk'))
        ->assertConflict()
        ->assertJsonPath('code', 'conflict');
});

it('keeps rendering other database errors as server errors', function (): void {
    config(['app.debug' => false]);
    Route::get(apiPath('__db'), fn () => throw new QueryException('pgsql', 'select', [], new PDOException('syntax error')));

    $this->getJson(apiPath('__db'))
        ->assertStatus(500)
        ->assertJsonPath('code', 'server_error');
});
