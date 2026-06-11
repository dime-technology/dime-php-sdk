<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A deposit (sweep) together with the transactions that make it up, as returned
 * by the deposit show and list-with-trans endpoints.
 */
final class DepositWithTransactions
{
    /**
     * @param  array<int, Transaction>  $transactions
     */
    public function __construct(
        public readonly ?string $sid,
        public readonly ?string $transactionInfoId,
        public readonly ?string $transactionId,
        public readonly ?string $transactionDate,
        public readonly ?string $fundDate,
        public readonly ?string $type,
        public readonly ?int $countOfTransactions,
        public readonly ?string $transTotal,
        public readonly array $transactions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sid: Arr::string($data, 'sid'),
            transactionInfoId: Arr::string($data, 'transaction_info_id'),
            transactionId: Arr::string($data, 'transaction_id'),
            transactionDate: Arr::string($data, 'transaction_date'),
            fundDate: Arr::string($data, 'fund_date'),
            type: Arr::string($data, 'type'),
            countOfTransactions: Arr::int($data, 'countOfTransactions'),
            transTotal: Arr::string($data, 'transTotal'),
            transactions: array_map(
                static fn (array $t): Transaction => Transaction::fromArray($t),
                array_values(Arr::arrayFrom($data, ['transactions'])),
            ),
        );
    }
}
