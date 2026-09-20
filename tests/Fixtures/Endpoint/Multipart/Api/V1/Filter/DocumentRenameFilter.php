<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Filter;

use Spiral\Filters\Attribute\Input\Post;

final class DocumentRenameFilter
{
    #[Post]
    public string $name;
}
