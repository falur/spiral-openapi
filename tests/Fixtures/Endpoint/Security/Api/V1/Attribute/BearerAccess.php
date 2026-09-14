<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Security\Api\V1\Attribute;

#[\Attribute(flags: \Attribute::TARGET_METHOD)]
final class BearerAccess {}
