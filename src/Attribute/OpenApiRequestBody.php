<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use Spiral\Attributes\NamedArgumentConstructor;

#[\Attribute(\Attribute::TARGET_CLASS), NamedArgumentConstructor]
final readonly class OpenApiRequestBody
{
    /**
     * @param list<non-empty-string> $requiredAnyOf
     */
    public function __construct(
        public array $requiredAnyOf = [],
        public bool|null $additionalProperties = null,
    ) {}
}
