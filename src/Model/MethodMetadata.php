<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Model;

final readonly class MethodMetadata
{
    /**
     * @param list<ParameterMetadata> $parameters
     * @param list<AdditionalResponseMetadata> $additionalResponses
     * @param list<int> $errorStatuses
     * @param list<string> $attributeClasses
     */
    public function __construct(public string $name, public string $summary, public string|null $returnType, public GenericReturnType|null $genericReturnType, public FileResponseMetadata|null $fileResponse, public RouteMetadata|null $route, public OpenApiMetadata|null $openApi, public array $parameters, public array $additionalResponses = [], public array $errorStatuses = [], public array $attributeClasses = [], public string|null $tag = null) {}
}
