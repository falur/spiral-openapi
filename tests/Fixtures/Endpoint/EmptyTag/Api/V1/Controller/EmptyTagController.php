<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\EmptyTag\Api\V1\Controller;

use Spiral\Router\Annotation\Route;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiTag;
use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\EmptyTag\Api\V1\Resource\EmptyTagResource;

final class EmptyTagController
{
    /**
     * @return DataResponse<EmptyTagResource>
     */
    #[OpenApiTag('   ')]
    #[Route(route: '/api/v1/empty-tag', name: 'api.v1.empty-tag', methods: ['GET'], group: 'api')]
    public function show(): DataResponse
    {
        return new DataResponse(new EmptyTagResource(id: 'fixture'));
    }
}
