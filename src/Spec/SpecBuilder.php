<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Spec;

use Spiral\Translator\TranslatorInterface;
use GianTiaga\SpiralOpenApi\Config\OpenApiGeneratorConfig;
use GianTiaga\SpiralOpenApi\Exception\OpenApiGenerationException;
use GianTiaga\SpiralOpenApi\Logging\DebugLogger;
use GianTiaga\SpiralOpenApi\Model\ClassMetadata;
use GianTiaga\SpiralOpenApi\Model\MethodMetadata;
use GianTiaga\SpiralOpenApi\Model\OperationSecurity;
use GianTiaga\SpiralOpenApi\Model\PropertyMetadata;
use GianTiaga\SpiralOpenApi\Response\Enum\ContentType;
use GianTiaga\SpiralOpenApi\Schema\NullableSchema;
use GianTiaga\SpiralOpenApi\Schema\SchemaBuilder;
use GianTiaga\SpiralOpenApi\Schema\SchemaRegistry;

final readonly class SpecBuilder
{
    public function __construct(private DebugLogger $logger, private TranslatorInterface $translator) {}
    /**
     * @param list<ClassMetadata> $classes
     */
    public function build(array $classes, OpenApiGeneratorConfig $config): OpenApiBuildResult
    {
        $classesByName = $this->classesByName($classes);
        $classesByShortName = $this->classesByShortName($classes);
        $schemaRegistry = new SchemaRegistry();
        $nullableSchema = new NullableSchema(openApiVersion: $config->openApiVersion);
        $schemaBuilder = new SchemaBuilder(classesByName: $classesByName, classesByShortName: $classesByShortName, schemaRegistry: $schemaRegistry, nullableSchema: $nullableSchema);
        $paths = [];
        $operationIds = [];
        $operationCount = 0;
        foreach ($classes as $classMetadata) {
            foreach ($classMetadata->methods as $methodMetadata) {
                if ($methodMetadata->route === null) {
                    continue;
                }
                if ($methodMetadata->openApi?->ignore === true) {
                    continue;
                }
                $this->logger->debug(\sprintf('Найдена OpenAPI-операция: %s::%s.', $classMetadata->className, $methodMetadata->name));
                $operationId = $this->operationId(classMetadata: $classMetadata, methodMetadata: $methodMetadata);
                if (isset($operationIds[$operationId])) {
                    throw new OpenApiGenerationException(\sprintf('Найден повторяющийся operationId: %s.', $operationId));
                }
                $operationIds[$operationId] = true;
                foreach ($methodMetadata->route->methods as $httpMethod) {
                    if ($this->headCoveredByGet(httpMethod: $httpMethod, routeMethods: $methodMetadata->route->methods)) {
                        continue;
                    }
                    $paths[$this->pathWithoutRoutePrefix(routePath: $methodMetadata->route->path, routePrefix: $config->routePrefix)][\strtolower($httpMethod)] = $this->operation(classMetadata: $classMetadata, methodMetadata: $methodMetadata, operationId: $operationId, schemaBuilder: $schemaBuilder, classesByName: $classesByName, config: $config, nullableSchema: $nullableSchema);
                    $operationCount++;
                }
            }
        }
        $this->ensureErrorResponseSchema(schemaRegistry: $schemaRegistry, config: $config, nullableSchema: $nullableSchema);
        $components = ['schemas' => $schemaRegistry->all()];
        if ($config->bearerSecurity !== null) {
            $components['securitySchemes'] = [
                $config->bearerSecurity->schemeName => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ];
        }
        return new OpenApiBuildResult(
            spec: ['openapi' => $config->openApiVersion, 'info' => ['title' => $config->title, 'version' => $config->version], 'servers' => [['url' => $config->routePrefix]], 'paths' => $paths, 'components' => $components],
            operationCount: $operationCount,
            schemaCount: $schemaRegistry->count(),
        );
    }
    /**
     * Описывать ли HEAD отдельной операцией.
     *
     * HTTP определяет HEAD как тот же GET без тела, поэтому обе строки пришли бы из одного метода
     * контроллера с одним operationId, а OpenAPI требует его уникальности по всей схеме. Маршрут,
     * объявивший HEAD рядом с GET, описывается одной операцией get.
     *
     * @param list<string> $routeMethods
     */
    private function headCoveredByGet(string $httpMethod, array $routeMethods): bool
    {
        if (\strtoupper($httpMethod) !== 'HEAD') {
            return false;
        }
        foreach ($routeMethods as $routeMethod) {
            if (\strtoupper($routeMethod) === 'GET') {
                return true;
            }
        }
        return false;
    }
    private function pathWithoutRoutePrefix(string $routePath, string $routePrefix): string
    {
        $normalizedRoutePath = $this->pathWithLeadingSlash(path: $routePath);
        $normalizedRoutePrefix = \rtrim(string: $this->pathWithLeadingSlash(path: $routePrefix), characters: '/');
        if ($normalizedRoutePrefix === '') {
            return $normalizedRoutePath;
        }
        if ($normalizedRoutePath === $normalizedRoutePrefix) {
            return '/';
        }
        if (!\str_starts_with(haystack: $normalizedRoutePath, needle: \sprintf('%s/', $normalizedRoutePrefix))) {
            return $normalizedRoutePath;
        }
        return $this->pathWithLeadingSlash(path: \substr(string: $normalizedRoutePath, offset: \strlen($normalizedRoutePrefix)));
    }
    private function pathWithLeadingSlash(string $path): string
    {
        // Spiral объявляет path-параметры как <name>, OpenAPI 3.1 требует {name}; нормализуем
        // ключ пути, иначе ключ вида /auth/sessions/<sessionId> делает спецификацию невалидной.
        $normalizedPath = \preg_replace(pattern: '/<([^>]+)>/', replacement: '{$1}', subject: $path) ?? $path;
        $trimmedPath = \trim(string: $normalizedPath, characters: '/');
        if ($trimmedPath === '') {
            return '/';
        }
        return \sprintf('/%s', $trimmedPath);
    }
    /**
     * @param array<string, ClassMetadata> $classesByName
     * @return array<string, mixed>
     */
    private function operation(ClassMetadata $classMetadata, MethodMetadata $methodMetadata, string $operationId, SchemaBuilder $schemaBuilder, array $classesByName, OpenApiGeneratorConfig $config, NullableSchema $nullableSchema): array
    {
        $successResponses = $this->successResponses(classMetadata: $classMetadata, methodMetadata: $methodMetadata, schemaBuilder: $schemaBuilder, config: $config, nullableSchema: $nullableSchema);
        $additionalResponses = $this->additionalResponses(classMetadata: $classMetadata, methodMetadata: $methodMetadata, schemaBuilder: $schemaBuilder, config: $config, nullableSchema: $nullableSchema, successResponses: $successResponses);
        $errorResponse = ['description' => $this->translator->trans(id: 'gian_tiaga.spiral_openapi.api_error'), 'content' => ['application/json' => ['schema' => $this->errorResponseSchema(config: $config)]]];
        $errorResponses = $this->errorResponses(
            classMetadata: $classMetadata,
            methodMetadata: $methodMetadata,
            occupiedResponses: $successResponses + $additionalResponses,
            errorResponse: $errorResponse,
        );
        $operation = ['operationId' => $operationId];
        $tag = $methodMetadata->tag;
        if ($tag !== null) {
            if (\trim($tag) === '') {
                throw new OpenApiGenerationException(\sprintf('Тег операции %s::%s пуст.', $classMetadata->className, $methodMetadata->name));
            }
            $operation['tags'] = [$tag];
        }
        $operation['description'] = $methodMetadata->openApi?->description ?: $methodMetadata->summary;
        $security = $this->security(methodMetadata: $methodMetadata, config: $config);
        if ($security === OperationSecurity::Public) {
            $operation['security'] = [];
        }
        if ($security === OperationSecurity::Bearer) {
            $bearerSecurity = $config->bearerSecurity
                ?? throw new OpenApiGenerationException('Для защищённой операции не настроена bearer-схема.');
            $operation['security'] = [[$bearerSecurity->schemeName => []]];
        }
        // Ключ `parameters` кладётся только при непустом списке, как и `requestBody`: спецификация
        // требует здесь массив, а пустой массив PHP печатается в YAML как `{}`, и операция с ним
        // не проходит валидацию.
        $parameters = $this->parameters(methodMetadata: $methodMetadata, schemaBuilder: $schemaBuilder, classesByName: $classesByName);
        if ($parameters !== []) {
            $operation['parameters'] = $parameters;
        }
        // Union (+) сохраняет числовой ключ кода ответа (200/204); spread [...$successResponses] переиндексировал бы его в 0.
        $operation['responses'] = $successResponses + $additionalResponses + $errorResponses + ['default' => $errorResponse];
        $requestBody = $this->requestBody(methodMetadata: $methodMetadata, schemaBuilder: $schemaBuilder, classesByName: $classesByName);
        if ($requestBody !== null) {
            $operation['requestBody'] = $requestBody;
        }
        return $operation;
    }

    private function security(MethodMetadata $methodMetadata, OpenApiGeneratorConfig $config): OperationSecurity|null
    {
        if ($config->bearerSecurity === null) {
            return null;
        }

        $public = \array_intersect(
            $methodMetadata->attributeClasses,
            $config->bearerSecurity->publicAccessAttributeClasses,
        ) !== [];
        $protected = \array_intersect(
            $methodMetadata->attributeClasses,
            $config->bearerSecurity->protectedAccessAttributeClasses,
        ) !== [];

        if ($public && $protected) {
            throw new OpenApiGenerationException(\sprintf(
                'Операция %s одновременно объявлена публичной и защищённой.',
                $methodMetadata->name,
            ));
        }
        if ($public) {
            return OperationSecurity::Public;
        }
        if ($protected) {
            return OperationSecurity::Bearer;
        }

        return null;
    }
    /**
     * @return array<int, mixed>
     */
    private function successResponses(ClassMetadata $classMetadata, MethodMetadata $methodMetadata, SchemaBuilder $schemaBuilder, OpenApiGeneratorConfig $config, NullableSchema $nullableSchema): array
    {
        if ($methodMetadata->returnType !== null && $methodMetadata->returnType === $config->responseWrapperMapping->emptyResponseClass) {
            $this->logger->debug(\sprintf('Операция %s::%s отдаёт 204 No Content (EmptySuccessResponse).', $classMetadata->className, $methodMetadata->name));
            return ['204' => ['description' => $this->translator->trans(id: 'gian_tiaga.spiral_openapi.successful_response')]];
        }
        $successStatus = $methodMetadata->openApi === null ? 200 : $methodMetadata->openApi->successStatus;
        return [$successStatus => $this->successResponse(classMetadata: $classMetadata, methodMetadata: $methodMetadata, schemaBuilder: $schemaBuilder, config: $config, nullableSchema: $nullableSchema)];
    }
    /**
     * Дополнительные коды ответа, объявленные атрибутом `#[OpenApiResponse]`.
     *
     * Успешный ответ метод описывает PHPDoc-дженериком, а другой код с тем же или своим телом —
     * атрибутом. Повтор уже занятого кода — ошибка объявления: молча выиграл бы один из них.
     *
     * @param array<int, mixed> $successResponses
     * @return array<int, mixed>
     */
    private function additionalResponses(ClassMetadata $classMetadata, MethodMetadata $methodMetadata, SchemaBuilder $schemaBuilder, OpenApiGeneratorConfig $config, NullableSchema $nullableSchema, array $successResponses): array
    {
        $responses = [];
        foreach ($methodMetadata->additionalResponses as $additionalResponse) {
            $statusKey = $additionalResponse->status;
            if (isset($successResponses[$statusKey]) || isset($responses[$statusKey])) {
                throw new OpenApiGenerationException(\sprintf('Код ответа %d объявлен у операции %s::%s дважды.', $additionalResponse->status, $classMetadata->className, $methodMetadata->name));
            }
            $this->logger->debug(\sprintf('Операция %s::%s объявляет дополнительный код ответа %d.', $classMetadata->className, $methodMetadata->name, $additionalResponse->status));
            $responses[$statusKey] = ['description' => $additionalResponse->description !== '' ? $additionalResponse->description : $this->translator->trans(id: 'gian_tiaga.spiral_openapi.additional_response'), 'content' => ['application/json' => ['schema' => $this->responseSchema(wrapperClass: $additionalResponse->wrapperClass, resourceClass: $additionalResponse->resourceClass, schemaBuilder: $schemaBuilder, config: $config, nullableSchema: $nullableSchema)]]];
        }
        return $responses;
    }
    /**
     * @param array<int, mixed> $occupiedResponses
     * @param array<string, mixed> $errorResponse
     * @return array<int, mixed>
     */
    private function errorResponses(ClassMetadata $classMetadata, MethodMetadata $methodMetadata, array $occupiedResponses, array $errorResponse): array
    {
        $responses = [];
        foreach ($methodMetadata->errorStatuses as $errorStatus) {
            if ($errorStatus < 400 || $errorStatus > 599) {
                throw new OpenApiGenerationException(\sprintf('Код ошибки %d у операции %s::%s не является 4xx или 5xx.', $errorStatus, $classMetadata->className, $methodMetadata->name));
            }
            if (isset($occupiedResponses[$errorStatus]) || isset($responses[$errorStatus])) {
                throw new OpenApiGenerationException(\sprintf('Код ответа %d объявлен у операции %s::%s дважды.', $errorStatus, $classMetadata->className, $methodMetadata->name));
            }
            $responses[$errorStatus] = $errorResponse;
        }
        return $responses;
    }
    /**
     * @return array<string, mixed>
     */
    private function successResponse(ClassMetadata $classMetadata, MethodMetadata $methodMetadata, SchemaBuilder $schemaBuilder, OpenApiGeneratorConfig $config, NullableSchema $nullableSchema): array
    {
        if ($methodMetadata->fileResponse !== null) {
            return ['description' => $this->translator->trans(id: 'gian_tiaga.spiral_openapi.successful_response'), 'content' => [$this->mediaType(contentType: $methodMetadata->fileResponse->contentType) => ['schema' => $methodMetadata->fileResponse->binary ? ['type' => 'string', 'format' => 'binary'] : ['type' => 'string']]]];
        }
        $genericReturnType = $methodMetadata->genericReturnType;
        if ($genericReturnType === null) {
            throw new OpenApiGenerationException(\sprintf('Для метода %s::%s отсутствует PHPDoc @return с generic-типом ответа.', $classMetadata->className, $methodMetadata->name));
        }
        return ['description' => $this->translator->trans(id: 'gian_tiaga.spiral_openapi.successful_response'), 'content' => ['application/json' => ['schema' => $this->responseSchema(wrapperClass: $genericReturnType->wrapperClass, resourceClass: $genericReturnType->resourceClass, schemaBuilder: $schemaBuilder, config: $config, nullableSchema: $nullableSchema)]]];
    }
    private function mediaType(string $contentType): string
    {
        $mediaType = \strstr(haystack: $contentType, needle: ';', before_needle: true);
        return $mediaType === false ? $contentType : $mediaType;
    }
    /**
     * Схема ответа об ошибке.
     *
     * `errors` описан здесь же и необязателен: разбор запроса добавляет его к тем же `message` и
     * `code`, перечисляя каждое отклонённое поле. Без него в схеме клиент видел бы у ответа 422
     * только общий текст, а разбор поля искал бы наугад. Адрес поля идёт через точку, включая
     * поле вложенного объекта, а сообщения поля — всегда список строк.
     */
    private function ensureErrorResponseSchema(SchemaRegistry $schemaRegistry, OpenApiGeneratorConfig $config, NullableSchema $nullableSchema): void
    {
        $schemaName = $this->shortName(className: $config->responseWrapperMapping->errorResponseClass);
        if ($schemaRegistry->has($schemaName)) {
            return;
        }
        $schemaRegistry->add(schemaName: $schemaName, schema: ['type' => 'object', 'properties' => ['message' => ['type' => 'string'], 'code' => $nullableSchema->makeNullable(['type' => 'integer']), 'errors' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['field' => ['type' => 'string'], 'messages' => ['type' => 'array', 'items' => ['type' => 'string']]], 'required' => ['field', 'messages']]]], 'required' => ['message']]);
    }
    /**
     * @return array<string, string>
     */
    private function errorResponseSchema(OpenApiGeneratorConfig $config): array
    {
        return ['$ref' => \sprintf('#/components/schemas/%s', $this->shortName(className: $config->responseWrapperMapping->errorResponseClass))];
    }
    /**
     * @param array<string, ClassMetadata> $classesByName
     * @return list<mixed>
     */
    private function parameters(MethodMetadata $methodMetadata, SchemaBuilder $schemaBuilder, array $classesByName): array
    {
        $parameters = [];
        $pathParameterNames = [];
        foreach ($methodMetadata->parameters as $parameterMetadata) {
            if (!$this->pathContainsParameter(methodMetadata: $methodMetadata, parameterName: $parameterMetadata->name)) {
                continue;
            }
            $parameters[] = ['name' => $parameterMetadata->name, 'in' => 'path', 'required' => true, 'schema' => $schemaBuilder->schemaForType($parameterMetadata->type)];
            $pathParameterNames[$parameterMetadata->name] = true;
        }
        foreach ($this->filterProperties(methodMetadata: $methodMetadata, source: PropertyMetadata::SOURCE_PATH, classesByName: $classesByName) as $propertyMetadata) {
            if (isset($pathParameterNames[$propertyMetadata->name]) || !$this->pathContainsParameter(methodMetadata: $methodMetadata, parameterName: $propertyMetadata->name)) {
                continue;
            }
            $parameters[] = ['name' => $propertyMetadata->name, 'in' => 'path', 'required' => true, 'schema' => $schemaBuilder->schemaForProperty($propertyMetadata)];
            $pathParameterNames[$propertyMetadata->name] = true;
        }
        foreach ($this->filterProperties(methodMetadata: $methodMetadata, source: PropertyMetadata::SOURCE_QUERY, classesByName: $classesByName) as $propertyMetadata) {
            $parameters[] = ['name' => $this->queryParameterName($propertyMetadata), 'in' => 'query', 'required' => $propertyMetadata->isRequired(), 'schema' => $schemaBuilder->schemaForProperty($propertyMetadata)];
        }
        return $parameters;
    }
    /**
     * Тело запроса операции.
     *
     * Вид тела задаёт сам Filter: поле загружаемого файла передаётся только формой, поэтому
     * операция с таким полем принимает `multipart/form-data`, а остальные поля этого Filter
     * остаются в той же форме обычными полями. Filter без файла по-прежнему даёт
     * `application/json`.
     *
     * @param array<string, ClassMetadata> $classesByName
     * @return null|array<string, mixed>
     */
    private function requestBody(MethodMetadata $methodMetadata, SchemaBuilder $schemaBuilder, array $classesByName): array|null
    {
        $bodyProperties = [...$this->filterProperties(methodMetadata: $methodMetadata, source: PropertyMetadata::SOURCE_BODY, classesByName: $classesByName), ...$this->filterProperties(methodMetadata: $methodMetadata, source: PropertyMetadata::SOURCE_DATA, classesByName: $classesByName)];
        $fileProperties = $this->filterProperties(methodMetadata: $methodMetadata, source: PropertyMetadata::SOURCE_FILE, classesByName: $classesByName);
        if ($bodyProperties === [] && $fileProperties === []) {
            return null;
        }
        $properties = [];
        $required = [];
        foreach ([...$bodyProperties, ...$fileProperties] as $propertyMetadata) {
            $properties[$propertyMetadata->name] = $propertyMetadata->source === PropertyMetadata::SOURCE_FILE
                ? $this->fileSchema()
                : $schemaBuilder->schemaForProperty($propertyMetadata);
            if ($propertyMetadata->isRequired()) {
                $required[] = $propertyMetadata->name;
            }
        }
        $schema = ['type' => 'object', 'properties' => $properties];
        if ($required !== []) {
            $schema['required'] = $required;
        }
        $requestBodyMetadata = $this->filterRequestBodyMetadata(
            methodMetadata: $methodMetadata,
            classesByName: $classesByName,
        );
        if ($requestBodyMetadata !== null && $requestBodyMetadata->requiredAnyOf !== []) {
            $anyOf = [];
            foreach ($requestBodyMetadata->requiredAnyOf as $propertyName) {
                if (!isset($properties[$propertyName])) {
                    throw new OpenApiGenerationException(\sprintf(
                        'Поле %s из OpenApiRequestBody отсутствует в теле запроса.',
                        $propertyName,
                    ));
                }
                $anyOf[] = ['required' => [$propertyName]];
            }
            $schema['anyOf'] = $anyOf;
        }
        if ($requestBodyMetadata?->additionalProperties !== null) {
            $schema['additionalProperties'] = $requestBodyMetadata->additionalProperties;
        }
        $mediaType = $fileProperties === [] ? 'application/json' : ContentType::MultipartFormData->value;
        return ['required' => true, 'content' => [$mediaType => ['schema' => $schemaBuilder->referenceForSchema(schemaName: $this->requestBodySchemaName(methodMetadata: $methodMetadata, classesByName: $classesByName), schema: $schema)]]];
    }
    /**
     * Схема поля загружаемого файла.
     *
     * Двоичная строка — единственная форма файла в OpenAPI, и ограничения `OpenApiProperty`
     * к ней не применяются: длину и образец задают не байтам файла. Необязательность поля
     * выражает перечень `required` тела, а не обнуляемый тип.
     *
     * @return array<string, string>
     */
    private function fileSchema(): array
    {
        return ['type' => 'string', 'format' => 'binary'];
    }
    /**
     * Имя схемы тела запроса.
     *
     * Компоненты именуются короткими именами классов, и тело следует тому же правилу: его форму
     * задаёт Filter операции. Редкий случай нескольких Filter с телом даёт составное имя, иначе
     * разные наборы полей заняли бы одну схему.
     *
     * @param array<string, ClassMetadata> $classesByName
     */
    private function requestBodySchemaName(MethodMetadata $methodMetadata, array $classesByName): string
    {
        $shortNames = [];
        foreach ($methodMetadata->parameters as $parameterMetadata) {
            $classMetadata = $classesByName[$parameterMetadata->type] ?? null;
            if ($classMetadata === null) {
                continue;
            }
            foreach ($classMetadata->properties as $propertyMetadata) {
                if ($propertyMetadata->source !== PropertyMetadata::SOURCE_BODY && $propertyMetadata->source !== PropertyMetadata::SOURCE_DATA && $propertyMetadata->source !== PropertyMetadata::SOURCE_FILE) {
                    continue;
                }
                $shortNames[$classMetadata->shortName] = true;
                break;
            }
        }
        return \implode(separator: '', array: \array_keys($shortNames));
    }
    /**
     * @param array<string, ClassMetadata> $classesByName
     */
    private function filterRequestBodyMetadata(MethodMetadata $methodMetadata, array $classesByName): \GianTiaga\SpiralOpenApi\Model\RequestBodyMetadata|null
    {
        $metadata = null;
        foreach ($methodMetadata->parameters as $parameterMetadata) {
            $filterMetadata = $classesByName[$parameterMetadata->type]->requestBody ?? null;
            if ($filterMetadata === null) {
                continue;
            }
            if ($metadata !== null) {
                throw new OpenApiGenerationException('Операция не может объединять несколько OpenApiRequestBody.');
            }
            $metadata = $filterMetadata;
        }
        return $metadata;
    }
    /**
     * @return array<string, mixed>
     */
    private function responseSchema(string $wrapperClass, string $resourceClass, SchemaBuilder $schemaBuilder, OpenApiGeneratorConfig $config, NullableSchema $nullableSchema): array
    {
        $wrapperShortName = $this->shortName($wrapperClass);
        // Имя схемы обёртки складывается из ресурса и обёртки: форму ответа задают ровно они,
        // поэтому одинаковые ответы разных операций попадают в одну схему компонентов.
        $schemaName = \sprintf('%s%s', $this->shortName($resourceClass), $wrapperShortName);
        $resourceReference = $schemaBuilder->referenceFor($resourceClass);
        if ($this->shortName($config->responseWrapperMapping->dataResponseClass) === $wrapperShortName) {
            return $schemaBuilder->referenceForSchema(schemaName: $schemaName, schema: ['type' => 'object', 'properties' => ['data' => $resourceReference], 'required' => ['data']]);
        }
        if ($this->shortName($config->responseWrapperMapping->collectionResponseClass) === $wrapperShortName) {
            return $schemaBuilder->referenceForSchema(schemaName: $schemaName, schema: ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => $resourceReference]], 'required' => ['data']]);
        }
        if ($this->shortName($config->responseWrapperMapping->paginationResponseClass) === $wrapperShortName) {
            return $schemaBuilder->referenceForSchema(schemaName: $schemaName, schema: ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => $resourceReference], 'meta' => ['type' => 'object', 'properties' => ['nextCursor' => $nullableSchema->makeNullable(['type' => 'string']), 'limit' => ['type' => 'integer']], 'required' => ['nextCursor', 'limit']]], 'required' => ['data', 'meta']]);
        }
        throw new OpenApiGenerationException(\sprintf('Неизвестная обёртка ответа: %s.', $wrapperClass));
    }
    /**
     * @param array<string, ClassMetadata> $classesByName
     * @return list<PropertyMetadata>
     */
    private function filterProperties(MethodMetadata $methodMetadata, string $source, array $classesByName): array
    {
        $properties = [];
        foreach ($methodMetadata->parameters as $parameterMetadata) {
            $filterClass = $parameterMetadata->type;
            if (!isset($classesByName[$filterClass])) {
                continue;
            }
            foreach ($classesByName[$filterClass]->properties as $propertyMetadata) {
                if ($propertyMetadata->source === $source) {
                    $properties[] = $propertyMetadata;
                }
            }
        }
        return $properties;
    }
    /**
     * Имя query-параметра в схеме.
     *
     * Список приходит повторяемым параметром, а PHP собирает его в массив только у имени со
     * скобками: `status=a&status=b` он схлопывает до последнего значения. Скобки приписываются
     * самой схемой, потому что иначе клиент по её описанию отправил бы запрос, который приложение
     * поймёт иначе, и получил бы молча суженную выдачу вместо ошибки. Читается свойство
     * по-прежнему по имени без скобок: их снимает разбор строки запроса.
     */
    private function queryParameterName(PropertyMetadata $propertyMetadata): string
    {
        if ($propertyMetadata->listItemType === null) {
            return $propertyMetadata->name;
        }
        return \sprintf('%s[]', $propertyMetadata->name);
    }
    private function pathContainsParameter(MethodMetadata $methodMetadata, string $parameterName): bool
    {
        return $methodMetadata->route !== null && (\str_contains(haystack: $methodMetadata->route->path, needle: \sprintf('<%s>', $parameterName)) || \str_contains(haystack: $methodMetadata->route->path, needle: \sprintf('{%s}', $parameterName)));
    }
    private function operationId(ClassMetadata $classMetadata, MethodMetadata $methodMetadata): string
    {
        if ($methodMetadata->openApi !== null && $methodMetadata->openApi->id !== '') {
            return $methodMetadata->openApi->id;
        }
        if ($methodMetadata->route?->name !== null) {
            return \str_replace(search: '.', replace: '_', subject: $methodMetadata->route->name);
        }
        return \sprintf('%s_%s', $classMetadata->shortName, $methodMetadata->name);
    }
    /**
     * @param list<ClassMetadata> $classes
     * @return array<string, ClassMetadata>
     */
    private function classesByName(array $classes): array
    {
        $classesByName = [];
        foreach ($classes as $classMetadata) {
            $classesByName[$classMetadata->className] = $classMetadata;
        }
        return $classesByName;
    }
    /**
     * @param list<ClassMetadata> $classes
     * @return array<string, ClassMetadata>
     */
    private function classesByShortName(array $classes): array
    {
        $classesByShortName = [];
        foreach ($classes as $classMetadata) {
            $classesByShortName[$classMetadata->shortName] = $classMetadata;
        }
        return $classesByShortName;
    }
    private function shortName(string $className): string
    {
        $parts = \explode(separator: '\\', string: \trim(string: $className, characters: '\\'));
        return (string) \end($parts);
    }
}
