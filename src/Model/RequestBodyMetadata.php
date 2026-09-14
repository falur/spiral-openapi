<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Model;

final readonly class RequestBodyMetadata
{
    /**
     * @param list<string> $requiredAnyOf
     */
    public function __construct(
        public array $requiredAnyOf,
        public bool|null $additionalProperties,
    ) {}
}
