<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1\Resource;

use GianTiaga\SpiralOpenApi\Attribute\OpenApiProperty;

final readonly class RoleResource
{
    public function __construct(
        #[OpenApiProperty(minLength: 1, maxLength: 64)]
        public string $slug,
    ) {}
}
