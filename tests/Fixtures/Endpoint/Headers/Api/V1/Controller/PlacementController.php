<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Headers\Api\V1\Controller;

use GianTiaga\SpiralOpenApi\Attribute\OpenApi;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiTag;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiErrorResponses;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiResponseHeader;
use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Headers\Api\V1\Filter\PlacementFilter;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Headers\Api\V1\Resource\PlacementResource;
use Spiral\Router\Annotation\Route;

final class PlacementController
{
    /** @return DataResponse<PlacementResource> */
    #[Route(route: '/api/v1/placement', methods: ['POST'])]
    #[OpenApi(id: 'placement_create', successStatus: HttpStatus::Created)]
    #[OpenApiTag('Placement')]
    #[OpenApiErrorResponses(statuses: [HttpStatus::Conflict])]
    #[OpenApiResponseHeader(status: HttpStatus::Created, name: 'Idempotent-Replayed', description: 'Только при повторе.', value: 'true')]
    #[OpenApiResponseHeader(status: HttpStatus::Conflict, name: 'Retry-After', value: '1')]
    public function create(PlacementFilter $filter): DataResponse
    {
        return new DataResponse(new PlacementResource(true));
    }
}
