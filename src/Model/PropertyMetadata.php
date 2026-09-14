<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Model;

final readonly class PropertyMetadata
{
    public const string SOURCE_QUERY = 'query';
    public const string SOURCE_PATH = 'path';
    public const string SOURCE_BODY = 'body';
    public const string SOURCE_DATA = 'data';
    public const string SOURCE_NONE = 'none';
    /**
     * @param list<string> $unionTypes Классы-члены union-типа свойства (для oneOf). Пусто, если тип не
     *                                 является объединением классов.
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
        public bool $hasDefault,
        public string $source,
        public string|null $listItemType = null,
        public array $unionTypes = [],
        public string|null $format = null,
        public string|null $pattern = null,
        public int|null $minimum = null,
        public int|null $maximum = null,
        public int|null $minLength = null,
        public int|null $maxLength = null,
        public bool|null $uniqueItems = null,
        public string|null $itemsPattern = null,
        public int|null $itemsMinLength = null,
        public int|null $itemsMaxLength = null,
        public string|int|bool|null $defaultValue = null,
    ) {}
    public function isRequired(): bool
    {
        return !$this->nullable && !$this->hasDefault;
    }
}
