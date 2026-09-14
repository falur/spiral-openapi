<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use Spiral\Attributes\NamedArgumentConstructor;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER), NamedArgumentConstructor]
final readonly class OpenApiProperty
{
    /**
     * @param class-string|null $items
     */
    public function __construct(
        public string|null $format = null,
        public string|null $pattern = null,
        public int|null $minimum = null,
        public int|null $maximum = null,
        public int|null $minLength = null,
        public int|null $maxLength = null,
        public string|null $items = null,
        public bool|null $nullable = null,
        public bool|null $uniqueItems = null,
        public string|null $itemsPattern = null,
        public int|null $itemsMinLength = null,
        public int|null $itemsMaxLength = null,
    ) {}
}
