<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1;

enum RolePermission: string
{
    case Read = 'read';
    case Manage = 'manage';
}
