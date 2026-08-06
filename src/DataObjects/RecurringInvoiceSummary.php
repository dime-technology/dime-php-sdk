<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The lightweight recurring-invoice template shape returned by the list
 * endpoint. For the full template with line items and generated invoices see
 * {@see RecurringInvoice}.
 *
 * Note the API returns `status` and `recurrence_schedule` in PascalCase (e.g.
 * "Active", "Monthly"), unlike the lowercase invoice statuses.
 */
final class RecurringInvoiceSummary
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $status,
        public readonly ?string $recurrenceSchedule,
        public readonly ?string $paymentTerms,
        public readonly bool $coverFeeRequired,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?string $nextRunDate,
        public readonly ?string $lastRunDate,
        public readonly ?string $customerName,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            status: Arr::string($data, 'status'),
            recurrenceSchedule: Arr::string($data, 'recurrence_schedule'),
            paymentTerms: Arr::string($data, 'payment_terms'),
            coverFeeRequired: Arr::bool($data, 'cover_fee_required'),
            startDate: Arr::string($data, 'start_date'),
            endDate: Arr::string($data, 'end_date'),
            nextRunDate: Arr::string($data, 'next_run_date'),
            lastRunDate: Arr::string($data, 'last_run_date'),
            customerName: Arr::string($data, 'customer_name'),
        );
    }
}
