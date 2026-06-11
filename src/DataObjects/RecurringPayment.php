<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A recurring payment schedule as returned by the recurring-payment endpoints.
 *
 * Amounts are returned by the API as decimal strings and are preserved as
 * strings here to avoid float rounding.
 */
final class RecurringPayment
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $amount,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?string $recurrenceSchedule,
        public readonly ?string $lastRunDate,
        public readonly ?string $lastRunStatus,
        public readonly ?int $lastRunFailedCount,
        public readonly ?string $nextRunDate,
        public readonly ?string $status,
        public readonly ?string $pausedUntilDate,
        public readonly ?string $customerUuid,
        public readonly ?string $cancelledAt,
        public readonly ?string $error,
        public readonly RecurringPaymentMethod $paymentMethod,
        public readonly TransactionAddress $shippingAddress,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            name: Arr::string($data, 'name'),
            amount: Arr::string($data, 'amount'),
            startDate: Arr::string($data, 'start_date'),
            endDate: Arr::string($data, 'end_date'),
            recurrenceSchedule: Arr::string($data, 'recurrence_schedule'),
            lastRunDate: Arr::string($data, 'last_run_date'),
            lastRunStatus: Arr::string($data, 'last_run_status'),
            lastRunFailedCount: Arr::int($data, 'last_run_failed_count'),
            nextRunDate: Arr::string($data, 'next_run_date'),
            status: Arr::string($data, 'status'),
            pausedUntilDate: Arr::string($data, 'paused_until_date'),
            customerUuid: Arr::string($data, 'customer_uuid'),
            cancelledAt: Arr::string($data, 'cancelled_at'),
            error: Arr::string($data, 'error'),
            paymentMethod: RecurringPaymentMethod::fromArray(Arr::arrayFrom($data, ['payment_method'])),
            shippingAddress: TransactionAddress::fromArray(Arr::arrayFrom($data, ['shipping_address'])),
        );
    }
}
