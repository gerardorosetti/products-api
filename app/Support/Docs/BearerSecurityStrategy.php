<?php

declare(strict_types=1);

namespace App\Support\Docs;

use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

/**
 * Documents routes behind an auth middleware with an HTTP bearer scheme.
 * The token is an opaque Sanctum token, not a JWT.
 */
final class BearerSecurityStrategy extends MiddlewareAuthSecurityStrategy
{
    public function __construct()
    {
        parent::__construct(
            scheme: SecurityScheme::http('bearer')->setDescription(
                'Opaque Sanctum personal access token (not a JWT). Obtain it from POST /auth/login and send it as `Authorization: Bearer <token>`.',
            ),
        );
    }
}
