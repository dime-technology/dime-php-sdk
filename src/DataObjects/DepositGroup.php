<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The list-with-trans response: a merchant's deposits over a date range, each
 * carrying its constituent transactions.
 *
 * The API returns `data.deposits` as an object keyed by sweep id; the keys are
 * discarded here and the values are exposed as a list.
 */
final class DepositGroup
{
    /**
     * @param  array<int, DepositWithTransactions>  $deposits
     */
    public function __construct(
        public readonly ?string $sid,
        public readonly ?int $count,
        public readonly array $deposits,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sid: Arr::string($data, 'sid'),
            count: Arr::int($data, 'count'),
            deposits: array_map(
                static fn (array $d): DepositWithTransactions => DepositWithTransactions::fromArray($d),
                array_values(Arr::arrayFrom($data, ['deposits'])),
            ),
        );
    }
}
