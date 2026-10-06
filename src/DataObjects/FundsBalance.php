<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A held-funds merchant's balance at the processor, as returned by the funds
 * balance endpoint.
 *
 * `releasable` is the figure that matters, and the one a release is checked
 * against: `available` less `atRisk` (funded ACH still inside the return
 * window — `achSettlementDays` says how long), `owedToSplit` (a share already
 * promised to another account, moved overnight), `unresolved` (releases
 * requested but not yet confirmed) and `releaseFee` (what a release costs).
 *
 * Amounts are preserved as strings to avoid float rounding.
 */
final class FundsBalance
{
    public function __construct(
        public readonly ?string $sid,
        public readonly ?string $available,
        public readonly ?string $pending,
        public readonly ?string $reserve,
        public readonly ?string $atRisk,
        public readonly ?string $owedToSplit,
        public readonly ?string $unresolved,
        public readonly ?string $releaseFee,
        public readonly ?string $releasable,
        public readonly ?int $achSettlementDays,
        public readonly bool $achOutEnabled,
        public readonly ?string $achOutLimitRemaining,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sid: Arr::string($data, 'sid'),
            available: Arr::string($data, 'available'),
            pending: Arr::string($data, 'pending'),
            reserve: Arr::string($data, 'reserve'),
            atRisk: Arr::string($data, 'at_risk'),
            owedToSplit: Arr::string($data, 'owed_to_split'),
            unresolved: Arr::string($data, 'unresolved'),
            releaseFee: Arr::string($data, 'release_fee'),
            releasable: Arr::string($data, 'releasable'),
            achSettlementDays: Arr::int($data, 'ach_settlement_days'),
            achOutEnabled: Arr::bool($data, 'ach_out_enabled'),
            achOutLimitRemaining: Arr::string($data, 'ach_out_limit_remaining'),
        );
    }
}
