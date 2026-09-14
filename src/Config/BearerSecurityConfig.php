<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Config;

use GianTiaga\SpiralOpenApi\Exception\OpenApiConfigurationException;

final readonly class BearerSecurityConfig
{
    /**
     * @param list<string> $publicAccessAttributeClasses
     * @param list<string> $protectedAccessAttributeClasses
     */
    public function __construct(
        public string $schemeName,
        public array $publicAccessAttributeClasses,
        public array $protectedAccessAttributeClasses,
    ) {}

    public function validate(): void
    {
        if (\preg_match(pattern: '/^[A-Za-z0-9._-]+$/', subject: $this->schemeName) !== 1) {
            throw new OpenApiConfigurationException('Имя bearer-схемы должно содержать только латинские буквы, цифры, точку, дефис или подчёркивание.');
        }

        if ($this->publicAccessAttributeClasses === [] || $this->protectedAccessAttributeClasses === []) {
            throw new OpenApiConfigurationException('Для bearer-схемы нужны публичные и защищённые атрибуты доступа.');
        }

        if (\array_intersect($this->publicAccessAttributeClasses, $this->protectedAccessAttributeClasses) !== []) {
            throw new OpenApiConfigurationException('Один атрибут доступа нельзя объявить одновременно публичным и защищённым.');
        }
    }
}
