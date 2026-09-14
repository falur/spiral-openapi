<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Требует тег группы у каждой операции схемы.
 *
 * Тег задаёт группу маршрута в документации: без него операция уезжает в безымянную кучу и
 * страница документации перестаёт читаться. Требование включает приложение параметром
 * `requireOpenApiTag`: пакет не навязывает его тем, кто группирует операции иначе.
 *
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final readonly class RequireOpenApiTagRule implements Rule
{
    private const string ROUTE_ATTRIBUTE = 'Spiral\\Router\\Annotation\\Route';
    private const string OPEN_API_ATTRIBUTE = 'GianTiaga\\SpiralOpenApi\\Attribute\\OpenApi';
    private const string TAG_ATTRIBUTE = 'GianTiaga\\SpiralOpenApi\\Attribute\\OpenApiTag';
    private const string ERROR_IDENTIFIER = 'gianTiaga.spiralOpenApi.routeTagRequired';

    public function __construct(private bool $required) {}
    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->required) {
            return [];
        }
        $attributes = $this->attributes($node);
        if ($this->attribute(attributeClass: self::ROUTE_ATTRIBUTE, attributes: $attributes, scope: $scope) === null) {
            return [];
        }
        if ($this->ignored(attributes: $attributes, scope: $scope)) {
            return [];
        }
        if ($this->attribute(attributeClass: self::TAG_ATTRIBUTE, attributes: $attributes, scope: $scope) !== null) {
            return [];
        }
        return [RuleErrorBuilder::message('Маршрут обязан объявлять тег группы атрибутом OpenApiTag.')
            ->identifier(self::ERROR_IDENTIFIER)
            ->line($node->getStartLine())
            ->build()];
    }
    /**
     * @return list<Attribute>
     */
    private function attributes(Node\Stmt\ClassMethod $method): array
    {
        $attributes = [];
        foreach ($method->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attribute) {
                $attributes[] = $attribute;
            }
        }
        return $attributes;
    }
    /**
     * @param list<Attribute> $attributes
     */
    private function attribute(string $attributeClass, array $attributes, Scope $scope): Attribute|null
    {
        foreach ($attributes as $attribute) {
            if ($scope->resolveName($attribute->name) === $attributeClass) {
                return $attribute;
            }
        }
        return null;
    }
    /**
     * Маршрут, исключённый из схемы, операцией не становится и тега не требует.
     *
     * @param list<Attribute> $attributes
     */
    private function ignored(array $attributes, Scope $scope): bool
    {
        $openApi = $this->attribute(attributeClass: self::OPEN_API_ATTRIBUTE, attributes: $attributes, scope: $scope);
        if ($openApi === null) {
            return false;
        }
        foreach ($openApi->args as $position => $argument) {
            if ($argument->name?->toString() !== 'ignore' && !($argument->name === null && $position === 2)) {
                continue;
            }
            return $argument->value instanceof Expr\ConstFetch
                && \strtolower($argument->value->name->toString()) === 'true';
        }
        return false;
    }
}
