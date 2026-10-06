<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The response to a funds release: the release record and whether it was a
 * replay.
 *
 * `replayed` is true when the `idempotency_key` matched an earlier release and
 * the API returned that original record instead of sending money again. Read
 * `$result->release->status` for the outcome, not just the absence of an
 * exception — see {@see FundRelease}.
 */
final class FundReleaseResult
{
    public function __construct(
        public readonly ?string $sid,
        public readonly bool $replayed,
        public readonly FundRelease $release,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sid: Arr::string($data, 'sid'),
            replayed: Arr::bool($data, 'replayed'),
            release: FundRelease::fromArray(Arr::arrayFrom($data, ['release'])),
        );
    }
}
