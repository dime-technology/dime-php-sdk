<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A file from a document upload that could not be stored. The rest of the
 * batch was kept, so re-send only these.
 */
final class FailedDocumentUpload
{
    public function __construct(
        public readonly ?string $fileName,
        public readonly ?string $reason,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileName: Arr::string($data, 'file_name'),
            reason: Arr::string($data, 'reason'),
        );
    }
}
