<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Headers\Api\V1\Resource;

final readonly class PlacementResource
{
    public function __construct(public bool $created) {}
}
