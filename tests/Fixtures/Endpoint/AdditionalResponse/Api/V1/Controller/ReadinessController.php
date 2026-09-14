<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\AdditionalResponse\Api\V1\Controller;

use Spiral\Router\Annotation\Route;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiResponse;
use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\AdditionalResponse\Api\V1\Enum\ReadinessStatus;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\AdditionalResponse\Api\V1\Resource\ReadinessResource;

final class ReadinessController
{
    /**
     * Fixture readiness check with an explicit description.
     *
     * @return DataResponse<ReadinessResource>
     */
    #[Route(route: '/api/v1/readiness', name: 'api.v1.readiness', methods: ['GET'], group: 'api')]
    #[OpenApiResponse(status: HttpStatus::ServiceUnavailable, resource: ReadinessResource::class, description: 'Service is not ready.')]
    public function show(): DataResponse
    {
        return new DataResponse(new ReadinessResource(status: ReadinessStatus::Ready));
    }

    /**
     * Fixture readiness check without a description.
     *
     * @return DataResponse<ReadinessResource>
     */
    #[Route(route: '/api/v1/readiness/strict', name: 'api.v1.readiness.strict', methods: ['GET'], group: 'api')]
    #[OpenApiResponse(status: HttpStatus::ServiceUnavailable, resource: ReadinessResource::class)]
    public function strict(): DataResponse
    {
        return new DataResponse(new ReadinessResource(status: ReadinessStatus::Ready));
    }
}
