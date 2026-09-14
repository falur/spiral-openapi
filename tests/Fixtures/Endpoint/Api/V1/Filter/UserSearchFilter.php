<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Api\V1\Filter;

use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Api\V1\Enum\AccountStatus;
use Spiral\Filters\Attribute\Input\Query;

final class UserSearchFilter
{
    #[Query]
    public string $query;
    #[Query]
    public int $limit = 20;
    /** @var list<AccountStatus>|null */
    #[Query]
    public array|null $status = null;
}
