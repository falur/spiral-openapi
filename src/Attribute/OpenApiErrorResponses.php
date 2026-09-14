<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use Spiral\Attributes\NamedArgumentConstructor;

#[\Attribute(\Attribute::TARGET_METHOD), NamedArgumentConstructor]
final readonly class OpenApiErrorResponses
{
    /**
     * @param list<HttpStatus> $statuses
     */
    public function __construct(public array $statuses) {}
}
