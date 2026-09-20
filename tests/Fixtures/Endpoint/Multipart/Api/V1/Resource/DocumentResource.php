<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Resource;

final readonly class DocumentResource
{
    public function __construct(public string $id, public string $name) {}
}
