<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\FundReleaseResult;
use DimePayments\Sdk\DataObjects\FundsBalance;
use DimePayments\Sdk\DataObjects\ReleasableTransactions;
use DimePayments\Sdk\Exceptions\ApiException;
use DimePayments\Sdk\Exceptions\DimeException;

/**
 * Held-funds endpoints, for merchants on a tier that holds their money at the
 * processor rather than sweeping it to their bank automatically: reading the
 * balance, listing the payments that can be released, and releasing funds.
 *
 * For any other merchant these endpoints answer 422 (raised as an
 * {@see ApiException}) rather than a misleading zero.
 *
 * Every method takes the merchant `$sid` explicitly.
 */
final class Funds extends AbstractResource
{
    /**
     * Fetch the merchant's balance at the processor and how much of it is
     * releasable today. A 503 (raised as a ServerException) means the processor
     * could not be reached — not that the balance is zero.
     */
    public function balance(string $sid): FundsBalance
    {
        $raw = $this->transport->request('GET', 'funds/balance', $this->envelope(['sid' => $sid]));

        return FundsBalance::fromArray($raw['data'] ?? []);
    }

    /**
     * List the payments that can be released right now and what each pays out
     * — the list to choose from when releasing by `transaction_info_ids`.
     * Newest first, up to 500; `truncated` says whether there are more.
     */
    public function transactions(string $sid): ReleasableTransactions
    {
        $raw = $this->transport->request('GET', 'funds/transactions', $this->envelope(['sid' => $sid]));

        return ReleasableTransactions::fromArray($raw['data'] ?? []);
    }

    /**
     * Release part of the held balance to the merchant's bank account.
     * Available to affiliate keys only.
     *
     * Name what to release with exactly one of `amount` (dollars, at most two
     * decimal places) or `transaction_info_ids` (up to 100 payments from
     * {@see transactions()}; all or nothing — if any does not qualify, nothing
     * is released and the error body's `data.ineligible` says which and why).
     * Either way the amount is checked against a freshly calculated
     * `releasable` and is never reduced to fit.
     *
     * Always pass a fresh `idempotency_key` per intended release and reuse it
     * when retrying: a retry with the same key returns the original release
     * (with `replayed` set) instead of sending money again. The same key with a
     * different request is refused with 409, as is a second release while one is
     * in flight for the merchant.
     *
     * Check `$result->release->status`, not just for an exception: "released"
     * means sent, "failed" means declined with nothing moved (the API answers
     * 422, which the SDK returns here rather than throwing, since the release
     * was recorded), and "unknown" means no confirmation came back and it may
     * have gone through.
     *
     * @param  array{
     *     idempotency_key: string,
     *     amount?: int|float|string,
     *     transaction_info_ids?: array<int, int|string>
     * }  $attributes
     */
    public function release(string $sid, array $attributes): FundReleaseResult
    {
        if (isset($attributes['transaction_info_ids'])) {
            // The API only accepts these references as strings.
            $attributes['transaction_info_ids'] = array_map(
                static fn (int|string $id): string => (string) $id,
                array_values($attributes['transaction_info_ids']),
            );
        }

        try {
            $raw = $this->transport->request('POST', 'funds/release', $this->envelope(['sid' => $sid] + $attributes));
        } catch (DimeException $e) {
            // A declined release is still a recorded release: the API answers
            // 422 with the release record (status "failed"). Any other 422
            // (e.g. over `releasable`, or ineligible payments) carries no record
            // and is rethrown.
            $data = $e->getResponseBody()['data'] ?? null;

            if ($e->getStatusCode() === 422 && is_array($data) && is_array($data['release'] ?? null)) {
                /** @var array<string, mixed> $data */
                return FundReleaseResult::fromArray($data);
            }

            throw $e;
        }

        return FundReleaseResult::fromArray($raw['data'] ?? []);
    }
}
