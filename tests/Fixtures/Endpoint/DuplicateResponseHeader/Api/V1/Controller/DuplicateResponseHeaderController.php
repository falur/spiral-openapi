<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\DuplicateResponseHeader\Api\V1\Controller;

use GianTiaga\SpiralOpenApi\Attribute\OpenApiResponseHeader;
use GianTiaga\SpiralOpenApi\Response\EmptySuccessResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use Spiral\Router\Annotation\Route;

final class DuplicateResponseHeaderController
{
    #[Route(route: '/api/v1/headers', methods: ['GET'])]
    #[OpenApiResponseHeader(status: HttpStatus::NoContent, name: 'X-Trace', value: 'first')]
    #[OpenApiResponseHeader(status: HttpStatus::NoContent, name: 'x-trace', value: 'second')]
    public function show(): EmptySuccessResponse
    {
        return new EmptySuccessResponse();
    }
}
