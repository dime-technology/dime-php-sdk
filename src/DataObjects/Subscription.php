<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A customer's enrollment in a {@see SubscriptionPlan}, as returned by the
 * list, show, pause, resume, and cancel endpoints.
 *
 * The list endpoint omits `items`; the show and lifecycle endpoints include the
 * snapshotted line items. `status` is one of "Active", "Failed", "Paused",
 * "Cancelled", or "Ended". Monetary values are preserved as strings to avoid
 * float rounding.
 */
final class Subscription
{
    /**
     * @param  array<int, SubscriptionItem>  $items
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $subscriptionPlanId,
        public readonly ?string $planName,
        public readonly ?string $amount,
        public readonly ?string $recurrenceSchedule,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?string $lastRunDate,
        public readonly ?string $lastRunStatus,
        public readonly ?int $lastRunFailedCount,
        public readonly ?string $nextRunDate,
        public readonly ?string $status,
        public readonly ?string $pausedUntilDate,
        public readonly ?string $cancelledAt,
        public readonly ?string $cancelledBy,
        public readonly ?string $customerUuid,
        public readonly ?string $error,
        public readonly SubscriptionPaymentMethod $paymentMethod,
        public readonly array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            subscriptionPlanId: Arr::int($data, 'subscription_plan_id'),
            planName: Arr::string($data, 'plan_name'),
            amount: Arr::string($data, 'amount'),
            recurrenceSchedule: Arr::string($data, 'recurrence_schedule'),
            startDate: Arr::string($data, 'start_date'),
            endDate: Arr::string($data, 'end_date'),
            lastRunDate: Arr::string($data, 'last_run_date'),
            lastRunStatus: Arr::string($data, 'last_run_status'),
            lastRunFailedCount: Arr::int($data, 'last_run_failed_count'),
            nextRunDate: Arr::string($data, 'next_run_date'),
            status: Arr::string($data, 'status'),
            pausedUntilDate: Arr::string($data, 'paused_until_date'),
            cancelledAt: Arr::string($data, 'cancelled_at'),
            cancelledBy: Arr::string($data, 'cancelled_by'),
            customerUuid: Arr::string($data, 'customer_uuid'),
            error: Arr::string($data, 'error'),
            paymentMethod: SubscriptionPaymentMethod::fromArray(Arr::arrayFrom($data, ['payment_method'])),
            items: array_map(
                static fn (array $item): SubscriptionItem => SubscriptionItem::fromArray($item),
                array_values(Arr::arrayFrom($data, ['items'])),
            ),
        );
    }
}
