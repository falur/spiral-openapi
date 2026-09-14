<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Schema;

use GianTiaga\SpiralOpenApi\Exception\OpenApiGenerationException;
use GianTiaga\SpiralOpenApi\Model\ClassMetadata;
use GianTiaga\SpiralOpenApi\Model\PropertyMetadata;

final readonly class SchemaBuilder
{
    /**
     * @param array<string, ClassMetadata> $classesByName
     * @param array<string, ClassMetadata> $classesByShortName
     */
    public function __construct(private array $classesByName, private array $classesByShortName, private SchemaRegistry $schemaRegistry, private NullableSchema $nullableSchema) {}
    /**
     * @return array<string, mixed>
     */
    public function referenceFor(string $className): array
    {
        $classMetadata = $this->resolveClass($className);
        $this->ensureClassSchema($classMetadata);
        return ['$ref' => \sprintf('#/components/schemas/%s', $classMetadata->shortName)];
    }
    /**
     * Ссылка на именованную схему, собранную не из класса.
     *
     * Обёртка ответа и тело запроса формы класса не имеют, но повторяются во многих операциях.
     * Схема кладётся в компоненты один раз под своим именем, а операция ссылается на неё: иначе
     * одна и та же форма разъезжается по схеме копиями, и клиент API не получает для неё тип.
     * Имя уникально по содержанию, поэтому повторная регистрация ничего не меняет.
     *
     * @param array<string, mixed> $schema
     * @return array<string, string>
     */
    public function referenceForSchema(string $schemaName, array $schema): array
    {
        if (!$this->schemaRegistry->has($schemaName)) {
            $this->schemaRegistry->add(schemaName: $schemaName, schema: $schema);
        }
        return ['$ref' => \sprintf('#/components/schemas/%s', $schemaName)];
    }
    public function ensureClassSchema(ClassMetadata $classMetadata): void
    {
        if ($this->schemaRegistry->has($classMetadata->shortName)) {
            return;
        }
        if ($classMetadata->enum) {
            $this->schemaRegistry->add(schemaName: $classMetadata->shortName, schema: ['type' => 'string', 'enum' => $classMetadata->enumCases]);
            return;
        }
        $this->schemaRegistry->add(schemaName: $classMetadata->shortName, schema: ['type' => 'object', 'properties' => new \stdClass()]);
        $properties = [];
        $required = [];
        foreach ($classMetadata->properties as $propertyMetadata) {
            $properties[$propertyMetadata->name] = $this->schemaForProperty($propertyMetadata);
            if ($propertyMetadata->isRequired()) {
                $required[] = $propertyMetadata->name;
            }
        }
        $schema = ['type' => 'object', 'properties' => $properties];
        if ($required !== []) {
            $schema['required'] = $required;
        }
        $this->schemaRegistry->add(schemaName: $classMetadata->shortName, schema: $schema);
    }
    /**
     * @return array<string, mixed>
     */
    public function schemaForProperty(PropertyMetadata $propertyMetadata): array
    {
        $schema = $this->baseSchemaForProperty($propertyMetadata);
        if ($propertyMetadata->format !== null) {
            $schema['format'] = $propertyMetadata->format;
        }
        if ($propertyMetadata->pattern !== null) {
            $schema['pattern'] = $propertyMetadata->pattern;
        }
        if ($propertyMetadata->minimum !== null) {
            $schema['minimum'] = $propertyMetadata->minimum;
        }
        if ($propertyMetadata->maximum !== null) {
            $schema['maximum'] = $propertyMetadata->maximum;
        }
        if ($propertyMetadata->minLength !== null) {
            $schema['minLength'] = $propertyMetadata->minLength;
        }
        if ($propertyMetadata->maxLength !== null) {
            $schema['maxLength'] = $propertyMetadata->maxLength;
        }
        if ($propertyMetadata->uniqueItems !== null) {
            $schema['uniqueItems'] = $propertyMetadata->uniqueItems;
        }
        if ($propertyMetadata->itemsPattern !== null
            || $propertyMetadata->itemsMinLength !== null
            || $propertyMetadata->itemsMaxLength !== null
        ) {
            $items = $schema['items'] ?? null;
            if (!\is_array($items)) {
                throw new OpenApiGenerationException('Ограничения элементов применимы только к массиву.');
            }
            if ($propertyMetadata->itemsPattern !== null) {
                $items['pattern'] = $propertyMetadata->itemsPattern;
            }
            if ($propertyMetadata->itemsMinLength !== null) {
                $items['minLength'] = $propertyMetadata->itemsMinLength;
            }
            if ($propertyMetadata->itemsMaxLength !== null) {
                $items['maxLength'] = $propertyMetadata->itemsMaxLength;
            }
            $schema['items'] = $items;
        }
        if ($propertyMetadata->hasDefault && $propertyMetadata->defaultValue !== null) {
            $schema['default'] = $propertyMetadata->defaultValue;
        }
        if ($propertyMetadata->nullable) {
            return $this->nullableSchema->makeNullable($schema);
        }
        return $schema;
    }
    /**
     * @return array<string, mixed>
     */
    private function baseSchemaForProperty(PropertyMetadata $propertyMetadata): array
    {
        if ($propertyMetadata->listItemType !== null) {
            return ['type' => 'array', 'items' => $this->schemaForType($propertyMetadata->listItemType)];
        }
        if ($propertyMetadata->unionTypes !== []) {
            return ['oneOf' => \array_map(fn(string $unionType): array => $this->schemaForType($unionType), $propertyMetadata->unionTypes)];
        }
        return $this->schemaForType($propertyMetadata->type);
    }
    /**
     * @return array<string, mixed>
     */
    public function schemaForType(string $type): array
    {
        $baseType = \ltrim(string: $type, characters: '\\');
        return match ($baseType) {
            'string' => ['type' => 'string'],
            'int', 'integer' => ['type' => 'integer'],
            'float', 'double' => ['type' => 'number'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            'DateTimeImmutable', 'DateTimeInterface' => ['type' => 'string', 'format' => 'date-time'],
            default => $this->referenceFor($baseType),
        };
    }
    private function resolveClass(string $className): ClassMetadata
    {
        $normalizedClassName = \ltrim(string: $className, characters: '\\');
        if (isset($this->classesByName[$normalizedClassName])) {
            return $this->classesByName[$normalizedClassName];
        }
        if (isset($this->classesByShortName[$normalizedClassName])) {
            return $this->classesByShortName[$normalizedClassName];
        }
        throw new OpenApiGenerationException(\sprintf('Неизвестный тип для OpenAPI-схемы: %s.', $className));
    }
}
