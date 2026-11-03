<?php

declare(strict_types=1);

namespace App\Support\Docs;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\Type;

/**
 * Post-processing of the generated document.
 *
 * Amounts: the maximum (999999999999999.99) (999999999999999.99) is not representable as a JSON number, so the
 * generated schema shows a rounded 1e15. It is replaced with an exact textual description.
 */
final class ApiDocumentTransformer implements DocumentTransformer
{
    private const ROUNDED_MAX = 1.0E+15;

    private const DESCRIPTION = 'Decimal amount between 0 and 999999999999999.99 with at most 2 decimals. '
        .'Send it as a string to keep full precision; JSON numbers are accepted too.';

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        foreach ($document->components->schemas as $schema) {
            if ($schema instanceof Schema) {
                $this->visit($schema->type);
            }
        }

        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                foreach ($operation->parameters as $parameter) {
                    if ($parameter instanceof Parameter && $parameter->schema instanceof Schema) {
                        $this->visit($parameter->schema->type);
                    }
                }
            }
        }
    }

    private function visit(?Type $type): void
    {
        if ($type === null) {
            return;
        }

        if ($type instanceof NumberType && is_numeric($type->max) && (float) $type->max === self::ROUNDED_MAX) {
            $type->setMax(null);
            $type->setDescription(self::DESCRIPTION);

            return;
        }

        if ($type instanceof ObjectType) {
            foreach ($type->properties as $property) {
                $this->visit($property);
            }
        }

        if ($type instanceof ArrayType) {
            $this->visit($type->items);
        }
    }
}
