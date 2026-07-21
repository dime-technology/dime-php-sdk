<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A payment recorded against an {@see Invoice}.
 *
 * The amount is preserved as a string to avoid float rounding.
 */
final class InvoicePayment
{
    public function __construct(
        public readonly ?string $amount,
        public readonly ?string $paidAt,
        public readonly ?string $method,
        public readonly ?string $transactionId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: Arr::string($data, 'amount'),
            paidAt: Arr::string($data, 'paid_at'),
            method: Arr::string($data, 'method'),
            transactionId: Arr::string($data, 'transaction_id'),
        );
    }
}
