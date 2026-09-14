<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\AdditionalResponse\Api\V1\Resource;

use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\AdditionalResponse\Api\V1\Enum\ReadinessStatus;

final readonly class ReadinessResource extends AbstractResource
{
    public function __construct(public ReadinessStatus $status) {}
}
