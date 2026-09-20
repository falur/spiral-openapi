<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Controller;

use GianTiaga\SpiralOpenApi\Attribute\OpenApi;
use GianTiaga\SpiralOpenApi\Response\DataResponse;
use GianTiaga\SpiralOpenApi\Response\Enum\HttpStatus;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Filter\DocumentRenameFilter;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Filter\DocumentUploadFilter;
use GianTiaga\SpiralOpenApi\Tests\Fixtures\Endpoint\Multipart\Api\V1\Resource\DocumentResource;
use Spiral\Router\Annotation\Route;

final readonly class DocumentController
{
    /**
     * @return DataResponse<DocumentResource>
     */
    #[OpenApi(id: 'multipart_document_upload', successStatus: HttpStatus::Created)]
    #[Route(route: '/api/v1/documents', methods: ['POST'])]
    public function upload(DocumentUploadFilter $filter): DataResponse
    {
        return new DataResponse(new DocumentResource(id: $filter->clientId, name: 'document'));
    }

    /**
     * @return DataResponse<DocumentResource>
     */
    #[OpenApi(id: 'multipart_document_rename')]
    #[Route(route: '/api/v1/documents/<documentId>', methods: ['PATCH'])]
    public function rename(DocumentRenameFilter $filter): DataResponse
    {
        return new DataResponse(new DocumentResource(id: 'document', name: $filter->name));
    }
}
