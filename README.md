# Spiral OpenAPI

`gian-tiaga/spiral-openapi` генерирует OpenAPI `3.1.0` из типизированного HTTP-слоя Spiral-приложения.

Генератор читает:

- route attributes Spiral;
- DTO фильтров;
- resource DTO;
- enum;
- PHPDoc `@return` с response wrapper;
- атрибут `#[OpenApi]`.

## Установка

```bash
composer require gian-tiaga/spiral-openapi:^0.1.0
```

## Bootloader

```php
use GianTiaga\SpiralOpenApi\Bootloader\OpenApiToolsBootloader;

protected const LOAD = [
    OpenApiToolsBootloader::class,
];
```

Bootloader подключает переводы пакета и регистрирует `OpenApiGenerator`.

## Минимальная генерация

```php
use GianTiaga\SpiralOpenApi\Config\OpenApiGeneratorConfig;
use GianTiaga\SpiralOpenApi\Config\ResponseWrapperMapping;
use GianTiaga\SpiralOpenApi\OpenApiGenerator;
use GianTiaga\SpiralOpenApi\Response\CollectionResponse;
use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Response\EmptySuccessResponse;
use GianTiaga\SpiralOpenApi\Response\ErrorResponse;
use GianTiaga\SpiralOpenApi\Response\PaginationResponse;

$result = $generator->generate(new OpenApiGeneratorConfig(
    projectRoot: __DIR__,
    sourcePaths: [__DIR__ . '/app/src/Endpoint/Api/V1'],
    apiNamespace: 'App\\Endpoint\\Api\\V1',
    routePrefix: '/api/v1',
    outputFile: __DIR__ . '/public/openapi/openapi.yml',
    title: 'API',
    version: '1.0.0',
    responseWrapperMapping: new ResponseWrapperMapping(
        dataResponseClass: DataResponse::class,
        collectionResponseClass: CollectionResponse::class,
        paginationResponseClass: PaginationResponse::class,
        errorResponseClass: ErrorResponse::class,
        emptyResponseClass: EmptySuccessResponse::class,
    ),
));
```

`$result` содержит путь к YAML, число операций и число схем.

## Метаданные операции

```php
use GianTiaga\SpiralOpenApi\Attribute\OpenApi;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;

#[OpenApi(
    id: 'create_user',
    description: 'Создать сотрудника',
    successStatus: HttpStatus::Created,
)]
public function create(): DataResponse
{
    return new DataResponse(data: new UserResource(id: '...'));
}
```

Если `#[OpenApi]` не указан, `operationId` строится из имени маршрута, а описание берётся из PHPDoc summary метода.
Основной успешный код по умолчанию равен `200`; `successStatus` задаёт другой фактический код.

Чтобы исключить action из спецификации:

```php
#[OpenApi(ignore: true)]
```

## Тег операции

Группу операции в документации задаёт атрибут `#[OpenApiTag]`:

```php
use GianTiaga\SpiralOpenApi\Attribute\OpenApiTag;

#[OpenApiTag('Клиенты')]
#[Route(route: '/api/v1/clients', name: 'api.v1.clients.index', methods: ['GET'])]
public function index(): PaginationResponse
```

Операция получает `tags` с одним значением. Без атрибута ключа `tags` у операции нет; пустой
тег валит генерацию с `OpenApiGenerationException`.

Обязательность тега включает проект параметром PHPStan `requireOpenApiTag`:

```neon
parameters:
    requireOpenApiTag: true
```

Тогда правило `gianTiaga.spiralOpenApi.routeTagRequired` требует `#[OpenApiTag]` у каждого
метода с `#[Route]`, кроме исключённых из схемы через `#[OpenApi(ignore: true)]`. По умолчанию
параметр выключен.

## Bearer-аутентификация

Пакет не знает атрибуты доступа конкретного приложения. Приложение передаёт их классы через
настройку генератора:

```php
use GianTiaga\SpiralOpenApi\Config\BearerSecurityConfig;

bearerSecurity: new BearerSecurityConfig(
    schemeName: 'bearerAuth',
    publicAccessAttributeClasses: [PublicRoute::class],
    protectedAccessAttributeClasses: [AuthenticatedRoute::class, RequiresPermission::class],
),
```

Тогда схема содержит `components.securitySchemes` с HTTP bearer. Операция с публичным
атрибутом получает `security: []`, а операция с защищённым атрибутом — требование bearer-схемы.
Конкретное право остаётся правилом приложения и в bearer-схему не кодируется.

## Response wrappers

Метод controller-а должен возвращать wrapper и иметь PHPDoc generic:

```php
/**
 * @return DataResponse<HealthResource>
 */
public function show(): DataResponse
```

Поддерживаются:

- `DataResponse<T>` — один объект;
- `CollectionResponse<T>` — список объектов;
- `PaginationResponse<T>` — список с пагинацией;
- `ErrorResponse` — JSON-ошибка;
- `ValidationErrorResponse` — JSON-ошибка валидации;
- `HtmlResponse` — HTML;
- `FileContentResponse` — готовое содержимое файла или текста;
- `FileResponse` — локальный файл для скачивания.

## Дополнительный код ответа

Основной успешный ответ генератор берёт из PHPDoc `@return`. Если метод отдаёт тело ещё и под
другим кодом, этот код объявляется атрибутом `#[OpenApiResponse]`:

```php
use GianTiaga\SpiralOpenApi\Attribute\OpenApiResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;

/**
 * @return DataResponse<ReadinessResource>
 */
#[OpenApiResponse(
    status: HttpStatus::ServiceUnavailable,
    resource: ReadinessResource::class,
    description: 'Приложение не готово обслуживать запросы.',
)]
#[Route(route: '/api/v1/readiness', name: 'api.v1.readiness', methods: ['GET'])]
public function show(): DataResponse
```

Атрибут повторяемый: у операции может быть несколько дополнительных кодов. Аргументы:

- `status` — код ответа значением enum `HttpStatus`;
- `resource` — resource DTO тела ответа через `::class`;
- `wrapper` — обёртка ответа, по умолчанию `DataResponse::class`;
- `description` — описание ответа; без него подставляется перевод
  `gian_tiaga.spiral_openapi.additional_response`.

Код, уже занятый основным ответом операции, повторно объявить нельзя: генерация падает с
`OpenApiGenerationException`.

## Ошибки и ограничения входа

Применимые ошибки операции объявляются одним атрибутом:

```php
#[OpenApiErrorResponses(statuses: [
    HttpStatus::Unauthorized,
    HttpStatus::Forbidden,
    HttpStatus::UnprocessableEntity,
])]
```

Ограничения свойства Filter, которые должны попасть в схему, задаёт `#[OpenApiProperty]`:

```php
#[Query]
#[OpenApiProperty(format: 'uuid', pattern: '...')]
public string|null $cursor = null;

#[Query]
#[OpenApiProperty(minimum: 1, maximum: 100)]
public int $limit = 50;

#[Post]
#[OpenApiProperty(
    uniqueItems: true,
    itemsPattern: '^[a-z0-9-]+$',
    itemsMinLength: 1,
    itemsMaxLength: 64,
)]
public array $roleSlugs;

#[Post]
#[OpenApiProperty(format: 'email', minLength: 1, nullable: false)]
public string|null $email = null;
```

Генератор переносит значение по умолчанию свойства, ограничения числа и строки. Для массива
`items: Permission::class` задаёт тип элементов, `uniqueItems` — уникальность, а аргументы
`itemsPattern`, `itemsMinLength` и `itemsMaxLength` — ограничения каждого элемента. Аргумент
`nullable` явно переопределяет обнуляемость схемы: это нужно для необязательного свойства Filter,
которое хранит отсутствие как `null`, но не принимает JSON `null`. Атрибут работает и на
promoted-свойствах Resource. Свойство Filter без nullable-типа и default попадает в `required`.
Path-свойство Filter добавляется как обязательный параметр операции.

Если тело принимает несколько необязательных полей, но хотя бы одно из них обязательно должно
присутствовать, это задаётся на классе Filter:

```php
#[OpenApiRequestBody(
    requiredAnyOf: ['name', 'email'],
    additionalProperties: false,
)]
final class UpdateUserFilter
```

`requiredAnyOf` создаёт отдельную ветку `anyOf` с `required` для каждого названного поля.
`additionalProperties: false` не позволяет неизвестному полю сделать пустой PATCH формально
валидным.

Чтобы Spiral отдавал response DTO как HTTP-ответы, подключите interceptor:

```php
use GianTiaga\SpiralOpenApi\Response\Interceptor\HttpResponseInterceptor;

protected const array INTERCEPTORS = [
    HttpResponseInterceptor::class,
];
```

## File response

```php
use GianTiaga\SpiralOpenApi\Response\Enum\ContentType;
use GianTiaga\SpiralOpenApi\Response\FileContentResponse;
use GianTiaga\SpiralOpenApi\Response\FileResponse;

return new FileContentResponse(
    content: $yaml,
    contentType: ContentType::Yaml,
);

return new FileResponse(
    path: $path,
    contentType: ContentType::Pdf,
    filename: 'report.pdf',
);
```

`FileContentResponse` генерируется как `type: string`. `FileResponse` генерируется как `type: string, format: binary`.

Если локальный файл отсутствует или недоступен для чтения, `FileResponse` бросает техническое исключение на русском языке. Это ошибка разработки, а не пользовательский текст, поэтому она не переводится по locale.

## Locale

OpenAPI YAML создаётся на текущем locale Spiral translator. Для другого языка нужно запустить генерацию с другим locale.

Ключи переводов:

- `gian_tiaga.spiral_openapi.successful_response`;
- `gian_tiaga.spiral_openapi.api_error`;
- `gian_tiaga.spiral_openapi.additional_response`.

Поддерживаются `ru` и `en`. Тексты из `#[OpenApi(description: ...)]` и PHPDoc принадлежат приложению, поэтому пакет их не переводит.

## PHPStan

Подключите extension, чтобы проверять `#[OpenApi]`:

```neon
includes:
    - vendor/gian-tiaga/spiral-openapi/extension.neon
```

Правило проверяет, что `id`:

- не пустой;
- содержит только латинские буквы, цифры, `_`, `.`, `-`, `:`;
- не повторяется в одном анализе.

Идентификаторы ошибок:

- `gianTiaga.spiralOpenApi.emptyId`;
- `gianTiaga.spiralOpenApi.invalidId`;
- `gianTiaga.spiralOpenApi.duplicateId`.

## Локальная разработка

Чтобы править пакет рядом с приложением, подключите его каталог path repository —
путь считается от корня приложения:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../spiral-openapi",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Проверки пакета:

```bash
composer install
composer test
composer phpstan
```
