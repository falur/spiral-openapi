<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Model;

final readonly class AdditionalResponseMetadata
{
    public function __construct(public int $status, public string $wrapperClass, public string $resourceClass, public string $description) {}
}
