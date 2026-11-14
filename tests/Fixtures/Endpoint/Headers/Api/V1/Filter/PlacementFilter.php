<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Headers\Api\V1\Filter;

use Spiral\Filters\Attribute\Input\Header;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiProperty;

final class PlacementFilter
{
    #[Header(key: 'Idempotency-Key')]
    #[OpenApiProperty(format: 'uuid')]
    public string $idempotencyKey;

    #[Header(key: 'X-Trace')]
    public string|null $trace = null;
}
