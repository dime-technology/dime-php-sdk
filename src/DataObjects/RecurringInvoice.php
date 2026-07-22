<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A full recurring-invoice template as returned by the show, create, and cancel
 * endpoints, including its customer, line items, upcoming run dates, and the
 * (up to 50) most recent generated invoices.
 *
 * The list endpoint returns a lighter shape; see {@see RecurringInvoiceSummary}.
 * The API returns `status` and `recurrence_schedule` in PascalCase (e.g.
 * "Active", "Monthly").
 */
final class RecurringInvoice
{
    /**
     * @param  array<int, InvoiceLineItem>  $items
     * @param  array<int, string>  $upcomingRunDates
     * @param  array<int, InvoiceSummary>  $invoices
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $status,
        public readonly ?string $recurrenceSchedule,
        public readonly ?string $paymentTerms,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?string $nextRunDate,
        public readonly ?string $lastRunDate,
        public readonly ?string $thankYouNote,
        public readonly InvoiceCustomer $customer,
        public readonly array $items,
        public readonly array $upcomingRunDates,
        public readonly array $invoices,
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
            startDate: Arr::string($data, 'start_date'),
            endDate: Arr::string($data, 'end_date'),
            nextRunDate: Arr::string($data, 'next_run_date'),
            lastRunDate: Arr::string($data, 'last_run_date'),
            thankYouNote: Arr::string($data, 'thank_you_note'),
            customer: InvoiceCustomer::fromArray(Arr::arrayFrom($data, ['customer'])),
            items: array_map(
                static fn (array $item): InvoiceLineItem => InvoiceLineItem::fromArray($item),
                array_values(Arr::arrayFrom($data, ['items'])),
            ),
            upcomingRunDates: array_values(array_filter(
                array_map(
                    static fn (mixed $date): ?string => is_scalar($date) ? (string) $date : null,
                    array_values(Arr::arrayFrom($data, ['upcoming_run_dates'])),
                ),
                static fn (?string $date): bool => $date !== null,
            )),
            invoices: array_map(
                static fn (array $invoice): InvoiceSummary => InvoiceSummary::fromArray($invoice),
                array_values(Arr::arrayFrom($data, ['invoices'])),
            ),
        );
    }
}
