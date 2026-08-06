<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Invoice;
use DimePayments\Sdk\DataObjects\InvoiceItem;
use DimePayments\Sdk\DataObjects\InvoiceLink;
use DimePayments\Sdk\DataObjects\InvoiceSummary;
use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\RecurringInvoice;
use DimePayments\Sdk\DataObjects\RecurringInvoiceSummary;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Invoice endpoints: creating, reading, updating, sending, and paying invoices,
 * managing their line items and the Merchant items used to build them, and
 * managing recurring-invoice schedules.
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$attributes` (merged into the request's `data` envelope) or in
 * `$filters` (merged into the `filters` envelope). See each method's array
 * shape for the accepted keys.
 *
 * Identify the customer with `customer_uuid` — the same identifier the customer,
 * payment-method and address endpoints use, and the only one the `Customer` data
 * object exposes. `customer_id` remains accepted for integrations written against the
 * original contract; supply exactly one.
 */
final class Invoices extends AbstractResource
{
    /**
     * List invoices for a merchant, optionally filtered by status.
     *
     * `overdue` and `all` are filter-only values, not statuses an invoice can
     * hold — an overdue invoice is one that is still open past its due date,
     * which reads as `isOverdue` on the invoice itself.
     *
     * @param  array{status?: 'draft'|'sent'|'viewed'|'partially_paid'|'paid'|'void'|'refunded'|'overdue'|'all'}  $filters
     * @return CursorPage<InvoiceSummary>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'invoices',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): InvoiceSummary => InvoiceSummary::fromArray($item),
        );
    }

    /**
     * Show a single invoice with its line items, payments, and event history.
     */
    public function show(string $sid, int|string $invoiceId): Invoice
    {
        $raw = $this->transport->request('GET', 'invoice', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a draft invoice with its line items. Every line must reference a
     * Merchant `item_id`; the line name and unit price are snapshotted.
     *
     * Set `cover_fee_required` to make the customer pay the processing fee. The fee
     * is added on top of the invoice at payment time rather than becoming a line
     * item, so `total` stays the amount owed to the merchant — read
     * `$invoice->coverFeeQuote` for what the customer will actually be charged.
     * Omit the field to inherit the Merchant's invoice setting.
     *
     * @param  array{
     *     invoice_number?: string,
     *     customer_uuid?: string,
     *     customer_id?: int|string,
     *     customer_name: string,
     *     customer_email: string,
     *     payment_terms: 'due_on_receipt'|'net_15'|'net_30'|'net_60',
     *     issue_date?: string,
     *     thank_you_note?: string,
     *     allow_partial_payment?: bool,
     *     cover_fee_required?: bool,
     *     reminder_settings?: array<string, mixed>,
     *     lines: array<int, array{item_id: int|string, name: string, description?: string, quantity: int|float|string, unit_price: int|float|string}>
     * }  $attributes
     */
    public function create(string $sid, array $attributes): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/create', $this->envelope(['sid' => $sid] + $attributes));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Update a draft invoice. Only draft invoices can be edited. Passing
     * `lines` replaces the existing line items.
     *
     * @param  array{
     *     invoice_number?: string,
     *     customer_uuid?: string,
     *     customer_id?: int|string,
     *     customer_name: string,
     *     customer_email: string,
     *     payment_terms: 'due_on_receipt'|'net_15'|'net_30'|'net_60',
     *     issue_date?: string,
     *     thank_you_note?: string,
     *     allow_partial_payment?: bool,
     *     cover_fee_required?: bool,
     *     reminder_settings?: array<string, mixed>,
     *     lines: array<int, array{item_id: int|string, name: string, description?: string, quantity: int|float|string, unit_price: int|float|string}>
     * }  $attributes
     */
    public function update(string $sid, int|string $invoiceId, array $attributes): Invoice
    {
        $raw = $this->transport->request('PATCH', 'invoice/update', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ] + $attributes));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Delete (soft-delete) a draft invoice. Only draft invoices can be deleted.
     */
    public function delete(string $sid, int|string $invoiceId): MessageResult
    {
        $raw = $this->transport->request('POST', 'invoice/delete', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }

    /**
     * Email the invoice to the customer and advance it to Sent. A paid, void,
     * or refunded invoice cannot be sent.
     */
    public function send(string $sid, int|string $invoiceId): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/send', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Activate a draft invoice for payment WITHOUT emailing it (for merchants
     * who share the public link themselves), advancing Draft to Sent.
     */
    public function markSent(string $sid, int|string $invoiceId): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/mark-sent', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Void an invoice. Voiding is terminal — the invoice can no longer be paid.
     */
    public function void(string $sid, int|string $invoiceId): Invoice
    {
        $raw = $this->transport->request('PATCH', 'invoice/void', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Clone any invoice into a new draft (copying its line items) with a freshly
     * allocated invoice number.
     */
    public function duplicate(string $sid, int|string $invoiceId): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/duplicate', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Record a merchant-initiated (MOTO) payment against an open invoice,
     * charging a card (raw card or a stored `token`) or a bank account (ACH).
     * Omit `amount` to pay the full balance (partial amounts require the invoice
     * to allow them).
     *
     * On a cover-fee invoice the processing fee for `payment_type` is charged on
     * top of `amount`, so the card or bank account is debited more than the invoice
     * is credited. The invoice is credited `amount`; the fee appears as `coverFee`
     * on the matching entry in `$invoice->payments`. Because the card and ACH rates
     * differ, the same `amount` settles differently per `payment_type`.
     *
     * @param  array{
     *     payment_type: 'cc'|'ach',
     *     amount?: int|float|string,
     *     memo?: string,
     *     token?: string,
     *     cardholder_name?: string,
     *     card_number?: int|string,
     *     expiration_date?: string,
     *     cvv?: int|string,
     *     account_number?: int|string,
     *     routing_number?: int|string,
     *     account_type?: string,
     *     account_name?: string,
     *     billing_address?: array<string, mixed>
     * }  $attributes
     */
    public function pay(string $sid, int|string $invoiceId, array $attributes): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/pay', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ] + $attributes));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Get the public pay link (and token) for an invoice.
     */
    public function link(string $sid, int|string $invoiceId): InvoiceLink
    {
        $raw = $this->transport->request('GET', 'invoice/link', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ]));

        return InvoiceLink::fromArray($raw['data'] ?? []);
    }

    /**
     * Append a single line item to a draft invoice and recalculate totals.
     *
     * @param  array{
     *     item_id: int|string,
     *     name: string,
     *     description?: string,
     *     quantity: int|float|string,
     *     unit_price: int|float|string
     * }  $attributes
     */
    public function addLineItem(string $sid, int|string $invoiceId, array $attributes): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/line-item/add', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
        ] + $attributes));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Update a single line item on a draft invoice. Only the fields you pass are
     * changed.
     *
     * @param  array{
     *     item_id?: int|string,
     *     name?: string,
     *     description?: string,
     *     quantity?: int|float|string,
     *     unit_price?: int|float|string
     * }  $attributes
     */
    public function updateLineItem(string $sid, int|string $invoiceId, int|string $lineItemId, array $attributes): Invoice
    {
        $raw = $this->transport->request('PATCH', 'invoice/line-item/update', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
            'line_item_id' => $lineItemId,
        ] + $attributes));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Remove a single line item from a draft invoice and recalculate totals.
     */
    public function deleteLineItem(string $sid, int|string $invoiceId, int|string $lineItemId): Invoice
    {
        $raw = $this->transport->request('POST', 'invoice/line-item/delete', $this->envelope([
            'sid' => $sid,
            'invoice_id' => $invoiceId,
            'line_item_id' => $lineItemId,
        ]));

        return Invoice::fromArray($raw['data'] ?? []);
    }

    /**
     * List the Merchant's items (funds/designations) available to use as invoice
     * line items. This endpoint is not paginated.
     *
     * @return array<int, InvoiceItem>
     */
    public function listItems(string $sid): array
    {
        $raw = $this->transport->request('GET', 'invoice/items', $this->envelope(['sid' => $sid]));

        return array_map(
            static fn (array $item): InvoiceItem => InvoiceItem::fromArray($item),
            array_values($raw['data'] ?? []),
        );
    }

    /**
     * Create an invoicing-only item (fund/designation) for the merchant, usable
     * as a line item's `item_id`.
     *
     * @param  array{
     *     name: string,
     *     description?: string,
     *     price?: int|float|string,
     *     tax_deductible?: bool
     * }  $attributes
     */
    public function createItem(string $sid, array $attributes): InvoiceItem
    {
        $raw = $this->transport->request('POST', 'invoice/item/create', $this->envelope(['sid' => $sid] + $attributes));

        return InvoiceItem::fromArray($raw['data'] ?? []);
    }

    /**
     * List recurring-invoice templates for a merchant, optionally filtered by
     * status.
     *
     * @param  array{status?: 'Active'|'Ended'|'Cancelled'}  $filters
     * @return CursorPage<RecurringInvoiceSummary>
     */
    public function listRecurring(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'recurring-invoices',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): RecurringInvoiceSummary => RecurringInvoiceSummary::fromArray($item),
        );
    }

    /**
     * Show a single recurring-invoice template with its line items, upcoming run
     * dates, and the 50 most recent generated invoices.
     */
    public function showRecurring(string $sid, int|string $recurringInvoiceId): RecurringInvoice
    {
        $raw = $this->transport->request('GET', 'recurring-invoice', $this->envelope([
            'sid' => $sid,
            'recurring_invoice_id' => $recurringInvoiceId,
        ]));

        return RecurringInvoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a recurring-invoice template. When `recurring_start_date` is today
     * the first invoice is generated and sent immediately.
     *
     * `cover_fee_required` is copied onto every invoice the template generates.
     *
     * @param  array{
     *     customer_uuid?: string,
     *     customer_id?: int|string,
     *     payment_terms: 'due_on_receipt'|'net_15'|'net_30'|'net_60',
     *     cover_fee_required?: bool,
     *     recurring_frequency: 'Weekly'|'Biweekly'|'FirstFifteenth'|'Monthly'|'Yearly',
     *     recurring_start_date: string,
     *     recurring_end_date?: string,
     *     thank_you_note?: string,
     *     lines: array<int, array{item_id: int|string, name: string, description?: string, quantity: int|float|string, unit_price: int|float|string}>
     * }  $attributes
     */
    public function createRecurring(string $sid, array $attributes): RecurringInvoice
    {
        $raw = $this->transport->request('POST', 'recurring-invoice/create', $this->envelope(['sid' => $sid] + $attributes));

        return RecurringInvoice::fromArray($raw['data'] ?? []);
    }

    /**
     * Cancel an active recurring-invoice template. No further invoices are
     * generated. Only active templates can be cancelled.
     */
    public function cancelRecurring(string $sid, int|string $recurringInvoiceId): RecurringInvoice
    {
        $raw = $this->transport->request('POST', 'recurring-invoice/cancel', $this->envelope([
            'sid' => $sid,
            'recurring_invoice_id' => $recurringInvoiceId,
        ]));

        return RecurringInvoice::fromArray($raw['data'] ?? []);
    }
}
