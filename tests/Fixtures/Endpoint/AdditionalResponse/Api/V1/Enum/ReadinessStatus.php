<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\AdditionalResponse\Api\V1\Enum;

enum ReadinessStatus: string
{
    case Ready = 'ready';
    case Unavailable = 'unavailable';
}
