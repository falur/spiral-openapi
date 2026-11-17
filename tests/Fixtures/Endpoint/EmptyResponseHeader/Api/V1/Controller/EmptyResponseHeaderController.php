<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\EmptyResponseHeader\Api\V1\Controller;

use GianTiaga\SpiralOpenApi\Attribute\OpenApiResponseHeader;
use GianTiaga\SpiralOpenApi\Response\EmptySuccessResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use Spiral\Router\Annotation\Route;

final class EmptyResponseHeaderController
{
    #[Route(route: '/api/v1/headers', methods: ['GET'])]
    #[OpenApiResponseHeader(status: HttpStatus::NoContent, name: '')]
    public function show(): EmptySuccessResponse
    {
        return new EmptySuccessResponse();
    }
}
