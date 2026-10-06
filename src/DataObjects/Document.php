<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A document held for a merchant, as returned by the document list endpoint
 * and (in a shorter form) by document upload.
 *
 * `docType` is one of "Verification", "FraudHolds", "Underwriting" or
 * "RetrievalRequest". The upload response carries only `uuid`, `fileName`,
 * `docType` and `size`; the remaining fields are null there.
 *
 * `sentToProcessorAt` and `processorResponse` stay null until Dime's team
 * forwards the document to the processor — uploading alone does not send it.
 */
final class Document
{
    public function __construct(
        public readonly ?string $uuid,
        public readonly ?string $fileName,
        public readonly ?string $docType,
        public readonly ?string $chargebackTransactionInfoId,
        public readonly ?int $size,
        public readonly ?string $uploadedAt,
        public readonly ?string $uploadedVia,
        public readonly ?string $sentToProcessorAt,
        public readonly ?string $processorResponse,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // `sent_to_processor` is null, or a [sent-at, processor response] pair.
        $sentToProcessor = array_values(Arr::arrayFrom($data, ['sent_to_processor']));
        $sentAt = $sentToProcessor[0] ?? null;
        $response = $sentToProcessor[1] ?? null;

        return new self(
            uuid: Arr::string($data, 'uuid'),
            fileName: Arr::string($data, 'file_name'),
            docType: Arr::string($data, 'doc_type'),
            chargebackTransactionInfoId: Arr::string($data, 'chargeback_transaction_info_id'),
            size: Arr::int($data, 'size'),
            uploadedAt: Arr::string($data, 'uploaded_at'),
            uploadedVia: Arr::string($data, 'uploaded_via'),
            sentToProcessorAt: is_scalar($sentAt) ? (string) $sentAt : null,
            processorResponse: is_scalar($response) ? (string) $response : null,
        );
    }
}
