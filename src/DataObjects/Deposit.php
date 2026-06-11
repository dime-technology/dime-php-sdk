<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A single deposit row as returned by the deposit list endpoint.
 *
 * Amounts are returned by the API as decimal strings and are preserved as
 * strings here to avoid float rounding.
 */
final class Deposit
{
    public function __construct(
        public readonly ?string $transactionDate,
        public readonly ?string $fundDate,
        public readonly ?string $transactionInfoId,
        public readonly ?string $transactionId,
        public readonly ?string $transactionDetailAccount,
        public readonly ?string $authorizationAmount,
        public readonly ?string $netAmount,
        public readonly ?string $sweepId,
        public readonly ?string $type,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionDate: Arr::string($data, 'transaction_date'),
            fundDate: Arr::string($data, 'fund_date'),
            transactionInfoId: Arr::string($data, 'transaction_info_id'),
            transactionId: Arr::string($data, 'transaction_id'),
            transactionDetailAccount: Arr::string($data, 'transaction_detail_account'),
            authorizationAmount: Arr::string($data, 'authorization_amount'),
            netAmount: Arr::string($data, 'net_amount'),
            sweepId: Arr::string($data, 'sweep_id'),
            type: Arr::string($data, 'type'),
        );
    }
}
