<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A full invoice as returned by the show, create, update, send, mark-sent,
 * void, duplicate, pay, and line-item endpoints, including its customer
 * snapshot, line items, recorded payments, and event log.
 *
 * The list endpoint returns a lighter shape; see {@see InvoiceSummary}.
 * Monetary values are preserved as strings to avoid float rounding.
 *
 * When `coverFeeRequired` is set the customer must also pay the processing fee,
 * which is quoted on {@see CoverFeeQuote} rather than included in `total` — so
 * what settles is more than what the invoice says. See that class for why.
 */
final class Invoice
{
    /**
     * @param  array<int, InvoiceLineItem>  $items
     * @param  array<int, InvoicePayment>  $payments
     * @param  array<int, InvoiceEvent>  $events
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $token,
        public readonly ?string $invoiceNumber,
        public readonly ?string $status,
        public readonly ?string $paymentTerms,
        public readonly ?string $issueDate,
        public readonly ?string $dueDate,
        public readonly bool $isOverdue,
        public readonly ?string $subtotal,
        public readonly ?string $total,
        public readonly ?string $amountPaid,
        public readonly ?string $balance,
        public readonly bool $allowPartialPayment,
        public readonly bool $coverFeeRequired,
        public readonly ?CoverFeeQuote $coverFeeQuote,
        public readonly ?string $thankYouNote,
        public readonly ?string $publicUrl,
        public readonly InvoiceCustomer $customer,
        public readonly array $items,
        public readonly array $payments,
        public readonly array $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            token: Arr::string($data, 'token'),
            invoiceNumber: Arr::string($data, 'invoice_number'),
            status: Arr::string($data, 'status'),
            paymentTerms: Arr::string($data, 'payment_terms'),
            issueDate: Arr::string($data, 'issue_date'),
            dueDate: Arr::string($data, 'due_date'),
            isOverdue: Arr::bool($data, 'is_overdue'),
            subtotal: Arr::string($data, 'subtotal'),
            total: Arr::string($data, 'total'),
            amountPaid: Arr::string($data, 'amount_paid'),
            balance: Arr::string($data, 'balance'),
            allowPartialPayment: Arr::bool($data, 'allow_partial_payment'),
            coverFeeRequired: Arr::bool($data, 'cover_fee_required'),
            coverFeeQuote: isset($data['cover_fee_quote']) && is_array($data['cover_fee_quote'])
                ? CoverFeeQuote::fromArray($data['cover_fee_quote'])
                : null,
            thankYouNote: Arr::string($data, 'thank_you_note'),
            publicUrl: Arr::string($data, 'public_url'),
            customer: InvoiceCustomer::fromArray(Arr::arrayFrom($data, ['customer'])),
            items: array_map(
                static fn (array $item): InvoiceLineItem => InvoiceLineItem::fromArray($item),
                array_values(Arr::arrayFrom($data, ['items'])),
            ),
            payments: array_map(
                static fn (array $payment): InvoicePayment => InvoicePayment::fromArray($payment),
                array_values(Arr::arrayFrom($data, ['payments'])),
            ),
            events: array_map(
                static fn (array $event): InvoiceEvent => InvoiceEvent::fromArray($event),
                array_values(Arr::arrayFrom($data, ['events'])),
            ),
        );
    }
}
