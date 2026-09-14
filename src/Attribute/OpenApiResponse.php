<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Attribute;

use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;

/**
 * Дополнительный код ответа операции со своей схемой тела.
 *
 * Основной успешный ответ генератор берёт из PHPDoc `@return`. Метод, который отдаёт тело
 * ещё и под другим кодом — например готовность приложения со статусом 503, — объявляет этот
 * код атрибутом: без объявления схема опишет только 200 и разойдётся с поведением.
 *
 * Атрибут повторяемый: у одной операции может быть несколько дополнительных кодов.
 */
#[\Attribute(flags: \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class OpenApiResponse
{
    /**
     * @param class-string $resource Resource DTO тела ответа.
     * @param class-string $wrapper Обёртка ответа: DataResponse, CollectionResponse или PaginationResponse.
     */
    public function __construct(public HttpStatus $status, public string $resource, public string $wrapper = DataResponse::class, public string $description = '') {}
}
