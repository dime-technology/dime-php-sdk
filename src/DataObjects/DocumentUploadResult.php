<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The result of a document upload: the documents that were stored and, when
 * some were not, which ones failed.
 *
 * Files are stored independently, so a partial failure still returns success
 * for the rest — check `failed` and re-send only those files.
 */
final class DocumentUploadResult
{
    /**
     * @param  array<int, Document>  $documents
     * @param  array<int, FailedDocumentUpload>  $failed
     */
    public function __construct(
        public readonly ?string $message,
        public readonly array $documents,
        public readonly array $failed,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            message: Arr::string($data, 'message'),
            documents: array_map(
                static fn (array $document): Document => Document::fromArray($document),
                array_values(Arr::arrayFrom($data, ['documents'])),
            ),
            failed: array_map(
                static fn (array $failure): FailedDocumentUpload => FailedDocumentUpload::fromArray($failure),
                array_values(Arr::arrayFrom($data, ['failed'])),
            ),
        );
    }
}
