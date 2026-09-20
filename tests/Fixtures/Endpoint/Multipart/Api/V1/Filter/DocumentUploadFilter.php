<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Filter;

use GianTiaga\SpiralOpenApi\Attribute\OpenApiProperty;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Filters\Attribute\Input\File;
use Spiral\Filters\Attribute\Input\Post;

final class DocumentUploadFilter
{
    /**
     * Загружаемый файл приходит из bag `files`, поэтому его тип обнуляем: отсутствующий файл
     * отсекает проверка входа. Схема объявляет поле обязательным, потому что операция без файла
     * смысла не имеет.
     */
    #[File]
    #[OpenApiProperty(nullable: false)]
    public UploadedFileInterface|null $file;

    #[Post]
    #[OpenApiProperty(format: 'uuid')]
    public string $clientId;
}
