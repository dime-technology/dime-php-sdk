<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The payments a held-funds merchant can release right now, newest first, as
 * returned by the funds transactions endpoint. This endpoint is not paginated:
 * it returns up to 500 payments and sets `truncated` when there are more.
 *
 * Being listed does not guarantee a payment can be released on its own; every
 * release is also capped by {@see FundsBalance::$releasable}. `total` is
 * preserved as a string to avoid float rounding.
 */
final class ReleasableTransactions
{
    /**
     * @param  array<int, ReleasableTransaction>  $transactions
     */
    public function __construct(
        public readonly ?string $sid,
        public readonly array $transactions,
        public readonly ?string $total,
        public readonly bool $truncated,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sid: Arr::string($data, 'sid'),
            transactions: array_map(
                static fn (array $transaction): ReleasableTransaction => ReleasableTransaction::fromArray($transaction),
                array_values(Arr::arrayFrom($data, ['transactions'])),
            ),
            total: Arr::string($data, 'total'),
            truncated: Arr::bool($data, 'truncated'),
        );
    }
}
