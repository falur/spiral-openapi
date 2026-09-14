<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use Spiral\Attributes\NamedArgumentConstructor;

#[\Attribute(\Attribute::TARGET_METHOD), NamedArgumentConstructor]
final readonly class OpenApiTag
{
    public function __construct(public string $name) {}
}
