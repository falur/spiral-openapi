<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1\Filter;

use GianTiaga\SpiralOpenApi\Attribute\OpenApiProperty;
use GianTiaga\SpiralOpenApi\Attribute\OpenApiRequestBody;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\ContractMetadata\Api\V1\RolePermission;
use Spiral\Filters\Attribute\Input\Post;
use Spiral\Filters\Attribute\Input\Query;
use Spiral\Filters\Attribute\Input\Route;

#[OpenApiRequestBody(requiredAnyOf: ['email', 'name'], additionalProperties: false)]
final class RoleFilter
{
    #[Route]
    #[OpenApiProperty(pattern: '^[a-z0-9-]+$', maxLength: 64)]
    public string $roleSlug;

    #[Query]
    #[OpenApiProperty(format: 'uuid', pattern: 'uuid-v7')]
    public string|null $cursor = null;

    #[Query]
    #[OpenApiProperty(minimum: 1, maximum: 100)]
    public int $limit = 50;

    /** @var list<string> */
    #[Post]
    #[OpenApiProperty(
        items: RolePermission::class,
        uniqueItems: true,
        itemsPattern: '^[a-z]+$',
        itemsMinLength: 1,
        itemsMaxLength: 32,
    )]
    public array $permissions;

    #[Post]
    #[OpenApiProperty(format: 'email', minLength: 1, nullable: false)]
    public string|null $email = null;

    #[Post]
    #[OpenApiProperty(minLength: 1, nullable: false)]
    public string|null $name = null;
}
