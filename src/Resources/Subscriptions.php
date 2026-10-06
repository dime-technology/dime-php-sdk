<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Subscription;
use DimePayments\Sdk\DataObjects\SubscriptionPlan;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Subscription endpoints: reading individual customer subscriptions
 * (enrollments in a {@see SubscriptionPlan}) and
 * controlling their lifecycle (pause/resume/cancel).
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$attributes`/`$filters` and merged into the request's `data`
 * envelope. Subscriptions themselves are created by subscribing a customer to a
 * plan; see {@see SubscriptionPlans::subscribe()}.
 */
final class Subscriptions extends AbstractResource
{
    /**
     * List subscriptions for a merchant, newest first, optionally filtered by
     * status or to a single customer.
     *
     * A merchant with no subscriptions returns an empty page, not an error.
     *
     * @param  array{status?: 'Active'|'Failed'|'Paused'|'Cancelled'|'Ended', customer_uuid?: string}  $filters
     * @return CursorPage<Subscription>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'subscription/list',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): Subscription => Subscription::fromArray($item),
        );
    }

    /**
     * Show a single subscription with its snapshotted line items.
     */
    public function show(string $sid, int|string $subscriptionId): Subscription
    {
        $raw = $this->transport->request('GET', 'subscription/show', $this->envelope([
            'sid' => $sid,
            'subscription_id' => $subscriptionId,
        ]));

        return Subscription::fromArray($raw['data'] ?? []);
    }

    /**
     * Pause an active subscription until `$pauseUntilDate` (a future date, e.g.
     * "2026-09-01"), or indefinitely when it is omitted.
     */
    public function pause(string $sid, int|string $subscriptionId, ?string $pauseUntilDate = null): Subscription
    {
        $raw = $this->transport->request('PATCH', 'subscription/pause', $this->envelope([
            'sid' => $sid,
            'subscription_id' => $subscriptionId,
            'pause_until_date' => $pauseUntilDate,
        ]));

        return Subscription::fromArray($raw['data'] ?? []);
    }

    /**
     * Resume a paused subscription, recomputing its next charge date.
     */
    public function resume(string $sid, int|string $subscriptionId): Subscription
    {
        $raw = $this->transport->request('PATCH', 'subscription/resume', $this->envelope([
            'sid' => $sid,
            'subscription_id' => $subscriptionId,
        ]));

        return Subscription::fromArray($raw['data'] ?? []);
    }

    /**
     * Cancel a subscription. Cancellation is terminal — no further charges are
     * made.
     */
    public function cancel(string $sid, int|string $subscriptionId): Subscription
    {
        $raw = $this->transport->request('PATCH', 'subscription/cancel', $this->envelope([
            'sid' => $sid,
            'subscription_id' => $subscriptionId,
        ]));

        return Subscription::fromArray($raw['data'] ?? []);
    }
}
