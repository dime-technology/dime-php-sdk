<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A payment recorded against an {@see Invoice}.
 *
 * `amount` is what was credited to the invoice; `coverFee` is the processing fee
 * charged on top of it, so `amount + coverFee` is what the customer actually paid.
 * It is zero unless the invoice required the customer to cover fees.
 *
 * Amounts are preserved as strings to avoid float rounding.
 */
final class InvoicePayment
{
    public function __construct(
        public readonly ?string $amount,
        public readonly ?string $coverFee,
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
            coverFee: Arr::string($data, 'cover_fee'),
            paidAt: Arr::string($data, 'paid_at'),
            method: Arr::string($data, 'method'),
            transactionId: Arr::string($data, 'transaction_id'),
        );
    }
}
