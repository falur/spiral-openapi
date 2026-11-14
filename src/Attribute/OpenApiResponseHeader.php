<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;

/** Заголовок уже объявленного ответа операции; условное наличие описывается description. */
#[\Attribute(flags: \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class OpenApiResponseHeader
{
    public function __construct(public HttpStatus $status, public string $name, public string $description = '', public string|null $value = null) {}
}
