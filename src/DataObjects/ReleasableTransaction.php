<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * One payment a held-funds merchant can release now, as listed by the funds
 * transactions endpoint. Pass its `transactionInfoId` to a release.
 *
 * `amount` is what releasing it pays out: `netAmount` less `splitAmount` (any
 * share owed to another account). `type` is "CC" or "ACH". Amounts are
 * preserved as strings to avoid float rounding.
 */
final class ReleasableTransaction
{
    public function __construct(
        public readonly ?string $transactionInfoId,
        public readonly ?string $type,
        public readonly ?string $transactionDate,
        public readonly ?string $grossAmount,
        public readonly ?string $netAmount,
        public readonly ?string $splitAmount,
        public readonly ?string $amount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionInfoId: Arr::string($data, 'transaction_info_id'),
            type: Arr::string($data, 'type'),
            transactionDate: Arr::string($data, 'transaction_date'),
            grossAmount: Arr::string($data, 'gross_amount'),
            netAmount: Arr::string($data, 'net_amount'),
            splitAmount: Arr::string($data, 'split_amount'),
            amount: Arr::string($data, 'amount'),
        );
    }
}
