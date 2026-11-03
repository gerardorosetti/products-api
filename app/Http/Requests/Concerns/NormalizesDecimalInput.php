<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Keeps full precision for amounts sent as JSON numbers.
 *
 * PHP casts floats to strings with the "precision" ini setting (14 digits), which silently
 * rounds values such as 1234567890123.45. Floats are converted to their shortest round-trip
 * representation instead; strings and integers are left untouched.
 */
trait NormalizesDecimalInput
{
    /**
     * @param  list<string>  $paths  dot-notation paths; "*" matches every item of a list
     */
    protected function normalizeDecimalInput(array $paths): void
    {
        $input = $this->all();

        foreach ($paths as $path) {
            $input = self::normalizeDecimalPath($input, explode('.', $path));
        }

        $this->merge($input);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @param  list<string>  $segments
     * @return array<array-key, mixed>
     */
    private static function normalizeDecimalPath(array $data, array $segments): array
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return $data;
        }

        $keys = $segment === '*' ? array_keys($data) : [$segment];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            if ($segments === []) {
                if (is_float($data[$key])) {
                    $data[$key] = json_encode($data[$key]);
                }

                continue;
            }

            if (is_array($data[$key])) {
                $data[$key] = self::normalizeDecimalPath($data[$key], $segments);
            }
        }

        return $data;
    }
}
