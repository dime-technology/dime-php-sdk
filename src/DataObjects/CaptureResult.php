<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The response to a capture: what was taken, beside the confirmation message.
 *
 * `transactionId` is the authorization's transaction number, as sent.
 * `authorizedAmount` is what the hold was for and `capturedAmount` what was
 * collected; a partial capture releases the difference. Amounts are preserved
 * as the API's two-decimal strings (e.g. "95.00") to avoid float rounding.
 * `status` is "captured" and `capturedAt` is an ISO 8601 timestamp.
 *
 * It extends {@see MessageResult}, so `$result->message` still reads as before.
 * The extra fields are null against an API that predates them.
 */
final class CaptureResult extends MessageResult
{
    public function __construct(
        ?string $message,
        public readonly ?string $transactionId,
        public readonly ?string $authorizedAmount,
        public readonly ?string $capturedAmount,
        public readonly ?string $status,
        public readonly ?string $capturedAt,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            message: Arr::string($data, 'message'),
            transactionId: Arr::string($data, 'transaction_id'),
            authorizedAmount: Arr::string($data, 'authorized_amount'),
            capturedAmount: Arr::string($data, 'captured_amount'),
            status: Arr::string($data, 'status'),
            capturedAt: Arr::string($data, 'captured_at'),
        );
    }
}
