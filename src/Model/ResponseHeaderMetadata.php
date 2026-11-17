<?php

declare(strict_types=1);

namespace GianTiaga\SpiralOpenApi\Model;

final readonly class ResponseHeaderMetadata
{
    public function __construct(public int $status, public string $name, public string $description, public string|null $value) {}
}
