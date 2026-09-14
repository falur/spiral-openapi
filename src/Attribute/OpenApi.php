<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use Spiral\Attributes\NamedArgumentConstructor;

#[\Attribute(\Attribute::TARGET_METHOD), NamedArgumentConstructor]
final readonly class OpenApi
{
    public function __construct(
        public string $id = '',
        public string $description = '',
        public bool $ignore = false,
        public HttpStatus $successStatus = HttpStatus::Ok,
    ) {}
}
