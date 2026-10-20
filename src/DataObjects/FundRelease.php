<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A release of held funds to the merchant's bank account.
 *
 * `status` is the outcome to act on: "released" (sent), "failed" (declined,
 * nothing moved — `failureReason` says why) or "unknown" (no confirmation came
 * back; it may have gone through, and its amount is held back from
 * `releasable` until reconciled). `transactionInfoIds` lists the payments
 * released when the release named them, and is empty for a release by amount.
 *
 * "returned" is what a "released" release becomes days later when the
 * merchant's own bank rejects the transfer and the money comes back.
 * `completedAt` still says when it was sent; `returnedAt` says when the bank
 * rejected it, `returnCode` gives the bank's reason (R02 account closed, R03 no
 * account, R04 invalid account number), and `returnFee` what the return cost.
 * `fee` keeps what the transfer was quoted at. `returnFee` can be null on a
 * returned release until the return fee has been reported.
 *
 * Amounts are preserved as strings to avoid float rounding.
 */
final class FundRelease
{
    /**
     * @param  array<int, string>  $transactionInfoIds
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $amount,
        public readonly ?string $fee,
        public readonly ?string $status,
        public readonly ?string $statusLabel,
        public readonly ?string $idempotencyKey,
        public readonly array $transactionInfoIds,
        public readonly ?string $failureReason,
        public readonly ?string $requestedAt,
        public readonly ?string $completedAt,
        public readonly ?string $returnedAt = null,
        public readonly ?string $returnCode = null,
        public readonly ?string $returnFee = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            amount: Arr::string($data, 'amount'),
            fee: Arr::string($data, 'fee'),
            status: Arr::string($data, 'status'),
            statusLabel: Arr::string($data, 'status_label'),
            idempotencyKey: Arr::string($data, 'idempotency_key'),
            transactionInfoIds: array_values(array_filter(
                array_map(
                    static fn (mixed $id): ?string => is_scalar($id) ? (string) $id : null,
                    array_values(Arr::arrayFrom($data, ['transaction_info_ids'])),
                ),
                static fn (?string $id): bool => $id !== null,
            )),
            failureReason: Arr::string($data, 'failure_reason'),
            requestedAt: Arr::string($data, 'requested_at'),
            completedAt: Arr::string($data, 'completed_at'),
            returnedAt: Arr::string($data, 'returned_at'),
            returnCode: Arr::string($data, 'return_code'),
            returnFee: Arr::string($data, 'return_fee'),
        );
    }
}
