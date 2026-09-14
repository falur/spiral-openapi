<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Security\Api\V1\Controller;

use GianTiaga\SpiralOpenApi\Response\EmptySuccessResponse;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Security\Api\V1\Attribute\BearerAccess;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Security\Api\V1\Attribute\PublicAccess;
use Spiral\Router\Annotation\Route;

final readonly class SecurityController
{
    #[PublicAccess]
    #[Route(route: '/api/v1/public', name: 'api.v1.public', methods: ['GET'])]
    public function showPublic(): EmptySuccessResponse
    {
        return new EmptySuccessResponse();
    }

    #[BearerAccess]
    #[Route(route: '/api/v1/protected', name: 'api.v1.protected', methods: ['GET'])]
    public function showProtected(): EmptySuccessResponse
    {
        return new EmptySuccessResponse();
    }
}
