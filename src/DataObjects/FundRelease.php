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
        );
    }
}
