<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1\Controller;

use GianTiaga\SpiralOpenApi\Attribute\OpenApi;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiErrorResponses;
use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1\Filter\RoleFilter;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1\Resource\RoleResource;
use Spiral\Router\Annotation\Route;

final readonly class RoleController
{
    /**
     * @return DataResponse<RoleResource>
     */
    #[OpenApi(id: 'contract_role_create', successStatus: HttpStatus::Created)]
    #[OpenApiErrorResponses(statuses: [HttpStatus::Unauthorized, HttpStatus::Conflict, HttpStatus::UnprocessableEntity])]
    #[Route(route: '/api/v1/roles/<roleSlug>', methods: ['POST'])]
    public function create(RoleFilter $filter): DataResponse
    {
        return new DataResponse(new RoleResource(slug: $filter->roleSlug));
    }
}
