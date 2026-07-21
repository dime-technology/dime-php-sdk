<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The lightweight invoice shape returned by the list endpoint (and reused for
 * the generated-invoice entries on a {@see RecurringInvoice}). For the full
 * invoice with line items, payments, and events see {@see Invoice}.
 *
 * Monetary values are preserved as strings to avoid float rounding.
 */
final class InvoiceSummary
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $invoiceNumber,
        public readonly ?string $status,
        public readonly ?string $customerName,
        public readonly ?string $customerEmail,
        public readonly ?string $total,
        public readonly ?string $amountPaid,
        public readonly ?string $balance,
        public readonly ?string $issueDate,
        public readonly ?string $dueDate,
        public readonly bool $isOverdue,
        public readonly ?string $publicUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            invoiceNumber: Arr::string($data, 'invoice_number'),
            status: Arr::string($data, 'status'),
            customerName: Arr::string($data, 'customer_name'),
            customerEmail: Arr::string($data, 'customer_email'),
            total: Arr::string($data, 'total'),
            amountPaid: Arr::string($data, 'amount_paid'),
            balance: Arr::string($data, 'balance'),
            issueDate: Arr::string($data, 'issue_date'),
            dueDate: Arr::string($data, 'due_date'),
            isOverdue: Arr::bool($data, 'is_overdue'),
            publicUrl: Arr::string($data, 'public_url'),
        );
    }
}
