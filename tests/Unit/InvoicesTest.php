<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Invoice;
use DimePayments\Sdk\DataObjects\InvoiceItem;
use DimePayments\Sdk\DataObjects\InvoiceLink;
use DimePayments\Sdk\DataObjects\InvoiceSummary;
use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\RecurringInvoice;
use DimePayments\Sdk\DataObjects\RecurringInvoiceSummary;

/**
 * The rich invoice shape returned by show/create/update/send/pay/etc.
 *
 * @return array<string, mixed>
 */
function fullInvoice(): array
{
    return [
        'id' => 1,
        'token' => 'keNlDtg1qYTFUyFrmHlEJAW6mdlwZCR2DmdkB4GT',
        'invoice_number' => 'INV-0001',
        'status' => 'sent',
        'payment_terms' => 'net_15',
        'issue_date' => '2026-07-20',
        'due_date' => '2026-08-04',
        'is_overdue' => false,
        'subtotal' => 300,
        'total' => 300,
        'amount_paid' => 0,
        'balance' => 300,
        'allow_partial_payment' => true,
        'thank_you_note' => 'Thanks for your support!',
        'public_url' => 'http://relictum.test/invoice/keNlDtg1qYTFUyFrmHlEJAW6mdlwZCR2DmdkB4GT',
        'customer' => ['id' => 52, 'name' => 'Shawn Maida', 'email' => 'shawn.maida@fostermade.co'],
        'items' => [
            ['id' => 1, 'item_id' => 96, 'name' => 'General', 'description' => null, 'quantity' => 2, 'unit_price' => 125, 'amount' => 250],
            ['id' => 2, 'item_id' => 397, 'name' => 'Online', 'description' => null, 'quantity' => 1, 'unit_price' => 50, 'amount' => 50],
        ],
        'payments' => [],
        'events' => [
            ['type' => 'sent', 'label' => 'Invoice sent', 'description' => 'Marked as sent via API', 'created_at' => '2026-07-20T10:18:43-04:00'],
            ['type' => 'created', 'label' => 'Invoice created', 'description' => 'Invoice created via API', 'created_at' => '2026-07-20T10:17:34-04:00'],
        ],
    ];
}

/**
 * The rich recurring-invoice template shape.
 *
 * @return array<string, mixed>
 */
function fullRecurringInvoice(): array
{
    return [
        'id' => 1,
        'status' => 'Active',
        'recurrence_schedule' => 'Monthly',
        'payment_terms' => 'net_15',
        'start_date' => '2026-08-15',
        'end_date' => null,
        'next_run_date' => '2026-08-15',
        'last_run_date' => null,
        'thank_you_note' => null,
        'customer' => ['id' => 52, 'name' => 'Shawn Maida', 'email' => 'shawn.maida@fostermade.co'],
        'items' => [
            ['id' => 1, 'item_id' => 96, 'name' => 'General', 'description' => null, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100],
        ],
        'upcoming_run_dates' => ['2026-08-15', '2026-09-15', '2026-10-15'],
        'invoices' => [],
    ];
}

it('lists invoices and paginates across cursors', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [[
                'id' => 1,
                'invoice_number' => 'INV-0001',
                'status' => 'sent',
                'customer_name' => 'Shawn Maida',
                'customer_email' => 'shawn.maida@fostermade.co',
                'total' => 300,
                'amount_paid' => 0,
                'balance' => 300,
                'issue_date' => '2026-07-20',
                'due_date' => '2026-08-04',
                'is_overdue' => false,
                'public_url' => 'http://relictum.test/invoice/abcd',
            ]],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['id' => 2, 'invoice_number' => 'INV-0002', 'is_overdue' => false]],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->invoices->list('000010', ['status' => 'sent']);

    expect($page->data[0])->toBeInstanceOf(InvoiceSummary::class)
        ->and($page->data[0]->invoiceNumber)->toBe('INV-0001')
        ->and($page->data[0]->total)->toBe('300')
        ->and($page->data[0]->customerName)->toBe('Shawn Maida')
        ->and($page->data[0]->isOverdue)->toBeFalse()
        ->and($page->hasMore())->toBeTrue();

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[1]->invoiceNumber)->toBe('INV-0002');
});

it('sends the data/filters envelope when listing', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => [], 'meta' => []])]);

    $client->invoices->list('000010', ['status' => 'sent']);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['status' => 'sent'],
    ]);
});

it('shows a full invoice with items, customer, and events', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $invoice = $client->invoices->show('000010', 42);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->invoiceNumber)->toBe('INV-0001')
        ->and($invoice->total)->toBe('300')
        ->and($invoice->allowPartialPayment)->toBeTrue()
        ->and($invoice->customer->name)->toBe('Shawn Maida')
        ->and($invoice->items)->toHaveCount(2)
        ->and($invoice->items[0]->itemId)->toBe(96)
        ->and($invoice->items[0]->unitPrice)->toBe('125')
        ->and($invoice->events)->toHaveCount(2)
        ->and($invoice->events[0]->type)->toBe('sent')
        ->and($invoice->payments)->toBe([]);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'invoice_id' => 42],
    ]);
});

it('creates an invoice and sends the line items', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()], 201)]);

    $invoice = $client->invoices->create('000010', [
        'customer_id' => 88,
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'payment_terms' => 'net_15',
        'lines' => [
            ['item_id' => 5, 'name' => 'Consulting', 'quantity' => 2, 'unit_price' => 125],
        ],
    ]);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->id)->toBe(1);

    $sent = sentJson($history);
    expect($sent['data']['sid'])->toBe('000010')
        ->and($sent['data']['customer_id'])->toBe(88)
        ->and($sent['data']['lines'][0])->toBe([
            'item_id' => 5, 'name' => 'Consulting', 'quantity' => 2, 'unit_price' => 125,
        ]);
});

it('forwards customer_uuid so a Customer can be linked without its integer id', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()], 201)]);

    $client->invoices->create('000010', [
        'customer_uuid' => '9f2a6c14-3e8b-4d21-9a77-5c1e0b8f4d33',
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'payment_terms' => 'net_15',
        'lines' => [
            ['item_id' => 5, 'name' => 'Consulting', 'quantity' => 2, 'unit_price' => 125],
        ],
    ]);

    $sent = sentJson($history);
    expect($sent['data']['customer_uuid'])->toBe('9f2a6c14-3e8b-4d21-9a77-5c1e0b8f4d33')
        ->and($sent['data'])->not->toHaveKey('customer_id');
});

it('forwards customer_uuid on a recurring invoice too', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullRecurringInvoice()], 201)]);

    $client->invoices->createRecurring('000010', [
        'customer_uuid' => '9f2a6c14-3e8b-4d21-9a77-5c1e0b8f4d33',
        'payment_terms' => 'net_30',
        'recurring_frequency' => 'Monthly',
        'recurring_start_date' => '2026-09-01',
        'lines' => [
            ['item_id' => 5, 'name' => 'Retainer', 'quantity' => 1, 'unit_price' => 500],
        ],
    ]);

    $sent = sentJson($history);
    expect($sent['data']['customer_uuid'])->toBe('9f2a6c14-3e8b-4d21-9a77-5c1e0b8f4d33')
        ->and($sent['data']['recurring_frequency'])->toBe('Monthly');
});

it('updates a draft invoice with the id merged into data', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $client->invoices->update('000010', 42, [
        'customer_id' => 88,
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'payment_terms' => 'net_30',
        'lines' => [['item_id' => 5, 'name' => 'Consulting', 'quantity' => 1, 'unit_price' => 125]],
    ]);

    $sent = sentJson($history);
    expect($sent['data']['sid'])->toBe('000010')
        ->and($sent['data']['invoice_id'])->toBe(42)
        ->and($sent['data']['payment_terms'])->toBe('net_30');
});

it('deletes a draft invoice and returns a message', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'Draft invoice deleted.']]),
    ]);

    $result = $client->invoices->delete('000010', 42);

    expect($result)->toBeInstanceOf(MessageResult::class)
        ->and($result->message)->toBe('Draft invoice deleted.');
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010', 'invoice_id' => 42]]);
});

it('sends an invoice', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $invoice = $client->invoices->send('000010', 42);

    expect($invoice->status)->toBe('sent');
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010', 'invoice_id' => 42]]);
});

it('marks an invoice sent without emailing', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $client->invoices->markSent('000010', 42);

    expect($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/invoice/mark-sent');
});

it('voids an invoice with a PATCH', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $invoice = $client->invoices->void('000010', 42);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/invoice/void');
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010', 'invoice_id' => 42]]);
});

it('duplicates an invoice', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()], 201)]);

    $client->invoices->duplicate('000010', 42);

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/invoice/duplicate');
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010', 'invoice_id' => 42]]);
});

it('pays an invoice with card details', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $invoice = $client->invoices->pay('000010', 42, [
        'payment_type' => 'cc',
        'amount' => 100,
        'token' => 'abc123',
        'memo' => 'Phone payment',
    ]);

    expect($invoice)->toBeInstanceOf(Invoice::class);

    $sent = sentJson($history);
    expect($sent['data'])->toBe([
        'sid' => '000010',
        'invoice_id' => 42,
        'payment_type' => 'cc',
        'amount' => 100,
        'token' => 'abc123',
        'memo' => 'Phone payment',
    ]);
});

it('gets the public pay link', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['public_url' => 'https://example.test/invoice/abcd', 'token' => 'abcd']]),
    ]);

    $link = $client->invoices->link('000010', 42);

    expect($link)->toBeInstanceOf(InvoiceLink::class)
        ->and($link->publicUrl)->toBe('https://example.test/invoice/abcd')
        ->and($link->token)->toBe('abcd');
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010', 'invoice_id' => 42]]);
});

it('adds a line item to a draft invoice', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $client->invoices->addLineItem('000010', 42, [
        'item_id' => 5,
        'name' => 'Consulting',
        'quantity' => 2,
        'unit_price' => 125,
    ]);

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/invoice/line-item/add');

    $sent = sentJson($history);
    expect($sent['data'])->toBe([
        'sid' => '000010',
        'invoice_id' => 42,
        'item_id' => 5,
        'name' => 'Consulting',
        'quantity' => 2,
        'unit_price' => 125,
    ]);
});

it('updates a line item with the ids merged into data', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $client->invoices->updateLineItem('000010', 42, 9, ['quantity' => 3]);

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/invoice/line-item/update');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'invoice_id' => 42, 'line_item_id' => 9, 'quantity' => 3],
    ]);
});

it('deletes a line item', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

    $client->invoices->deleteLineItem('000010', 42, 9);

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/invoice/line-item/delete');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'invoice_id' => 42, 'line_item_id' => 9],
    ]);
});

it('lists invoice items as a plain array (not paginated)', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            ['id' => 5, 'name' => 'Consulting', 'description' => null, 'price' => 125],
            ['id' => 6, 'name' => 'Design', 'description' => 'UI work', 'price' => 90],
        ]]),
    ]);

    $items = $client->invoices->listItems('000010');

    expect($items)->toBeArray()->toHaveCount(2)
        ->and($items[0])->toBeInstanceOf(InvoiceItem::class)
        ->and($items[0]->name)->toBe('Consulting')
        ->and($items[0]->price)->toBe('125')
        ->and($items[0]->taxDeductible)->toBeFalse();
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010']]);
});

it('creates an invoice item', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'id' => 5, 'name' => 'Consulting', 'description' => null, 'price' => 125, 'tax_deductible' => false,
        ]], 201),
    ]);

    $item = $client->invoices->createItem('000010', [
        'name' => 'Consulting',
        'description' => 'Professional services',
        'price' => 125,
        'tax_deductible' => false,
    ]);

    expect($item)->toBeInstanceOf(InvoiceItem::class)
        ->and($item->id)->toBe(5)
        ->and($item->price)->toBe('125');

    $sent = sentJson($history);
    expect($sent['data']['sid'])->toBe('000010')
        ->and($sent['data']['name'])->toBe('Consulting');
});

it('lists recurring invoices with cursor pagination', function () {
    [$client, $history] = fakeClient([
        jsonResponse([
            'data' => [[
                'id' => 1,
                'status' => 'Active',
                'recurrence_schedule' => 'Monthly',
                'payment_terms' => 'net_15',
                'start_date' => '2026-08-15',
                'end_date' => null,
                'next_run_date' => '2026-08-15',
                'last_run_date' => null,
                'customer_name' => 'Shawn Maida',
            ]],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => null],
        ]),
    ]);

    $page = $client->invoices->listRecurring('000010', ['status' => 'Active']);

    expect($page->data[0])->toBeInstanceOf(RecurringInvoiceSummary::class)
        ->and($page->data[0]->recurrenceSchedule)->toBe('Monthly')
        ->and($page->data[0]->customerName)->toBe('Shawn Maida')
        ->and($page->hasMore())->toBeFalse();

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['status' => 'Active'],
    ]);
});

it('shows a full recurring invoice with upcoming run dates', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullRecurringInvoice()])]);

    $recurring = $client->invoices->showRecurring('000010', 3);

    expect($recurring)->toBeInstanceOf(RecurringInvoice::class)
        ->and($recurring->status)->toBe('Active')
        ->and($recurring->recurrenceSchedule)->toBe('Monthly')
        ->and($recurring->customer->name)->toBe('Shawn Maida')
        ->and($recurring->items)->toHaveCount(1)
        ->and($recurring->upcomingRunDates)->toBe(['2026-08-15', '2026-09-15', '2026-10-15'])
        ->and($recurring->invoices)->toBe([]);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_invoice_id' => 3],
    ]);
});

it('creates a recurring invoice', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullRecurringInvoice()], 201)]);

    $recurring = $client->invoices->createRecurring('000010', [
        'customer_id' => 88,
        'payment_terms' => 'net_15',
        'recurring_frequency' => 'Monthly',
        'recurring_start_date' => '2026-08-01',
        'lines' => [['item_id' => 5, 'name' => 'Monthly retainer', 'quantity' => 1, 'unit_price' => 500]],
    ]);

    expect($recurring)->toBeInstanceOf(RecurringInvoice::class);

    $sent = sentJson($history);
    expect($sent['data']['sid'])->toBe('000010')
        ->and($sent['data']['recurring_frequency'])->toBe('Monthly')
        ->and($sent['data']['lines'][0]['unit_price'])->toBe(500);
});

it('cancels a recurring invoice', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullRecurringInvoice()])]);

    $client->invoices->cancelRecurring('000010', 3);

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/recurring-invoice/cancel');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_invoice_id' => 3],
    ]);
});

describe('required cover fees', function () {
    /**
     * A cover-fee invoice: the fee is quoted for both methods against the balance
     * and is deliberately absent from `total`, which stays the merchant's amount.
     *
     * @return array<string, mixed>
     */
    $quotedInvoice = function (): array {
        return array_merge(fullInvoice(), [
            'subtotal' => 100,
            'total' => 100,
            'balance' => 100,
            'cover_fee_required' => true,
            'cover_fee_quote' => [
                'basis' => 'balance',
                'base' => 100,
                'cc' => ['fee' => 4.32, 'total' => 104.32],
                'ach' => ['fee' => 1.26, 'total' => 101.26],
            ],
            'payments' => [
                ['amount' => 100, 'cover_fee' => 4.32, 'paid_at' => '2026-08-06T10:00:00-04:00', 'method' => '+CC', 'transaction_id' => 91],
            ],
        ]);
    };

    it('sends cover_fee_required on create', function () {
        [$client, $history] = fakeClient([jsonResponse(['data' => fullInvoice()], 201)]);

        $client->invoices->create('000010', [
            'customer_uuid' => '9f2a6c14-3e8b-4d21-9a77-5c1e0b8f4d33',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'payment_terms' => 'net_15',
            'cover_fee_required' => true,
            'lines' => [['item_id' => 5, 'name' => 'Consulting', 'quantity' => 1, 'unit_price' => 100]],
        ]);

        expect(sentJson($history)['data']['cover_fee_required'])->toBeTrue();
    });

    it('sends cover_fee_required on a recurring template', function () {
        [$client, $history] = fakeClient([jsonResponse(['data' => fullRecurringInvoice()], 201)]);

        $client->invoices->createRecurring('000010', [
            'customer_uuid' => '9f2a6c14-3e8b-4d21-9a77-5c1e0b8f4d33',
            'payment_terms' => 'net_30',
            'cover_fee_required' => true,
            'recurring_frequency' => 'Monthly',
            'recurring_start_date' => '2026-09-01',
            'lines' => [['item_id' => 5, 'name' => 'Retainer', 'quantity' => 1, 'unit_price' => 500]],
        ]);

        expect(sentJson($history)['data']['cover_fee_required'])->toBeTrue();
    });

    it('parses the per-method quote and keeps it out of total', function () use ($quotedInvoice) {
        [$client] = fakeClient([jsonResponse(['data' => $quotedInvoice()])]);

        $invoice = $client->invoices->show('000010', 1);

        expect($invoice->coverFeeRequired)->toBeTrue()
            ->and($invoice->coverFeeQuote)->not->toBeNull()
            ->and($invoice->coverFeeQuote->basis)->toBe('balance')
            ->and($invoice->coverFeeQuote->base)->toBe('100')
            ->and($invoice->coverFeeQuote->ccFee)->toBe('4.32')
            ->and($invoice->coverFeeQuote->ccTotal)->toBe('104.32')
            ->and($invoice->coverFeeQuote->achFee)->toBe('1.26')
            ->and($invoice->coverFeeQuote->achTotal)->toBe('101.26')
            // The merchant is still owed the invoice amount; the fee sits on top.
            ->and($invoice->total)->toBe('100');
    });

    it('exposes the fee charged alongside the amount credited on a payment', function () use ($quotedInvoice) {
        [$client] = fakeClient([jsonResponse(['data' => $quotedInvoice()])]);

        $payment = $client->invoices->show('000010', 1)->payments[0];

        // amount + coverFee is what the customer was actually charged.
        expect($payment->amount)->toBe('100')
            ->and($payment->coverFee)->toBe('4.32');
    });

    it('leaves the quote null when no fee is required', function () {
        [$client] = fakeClient([jsonResponse(['data' => fullInvoice()])]);

        $invoice = $client->invoices->show('000010', 1);

        expect($invoice->coverFeeRequired)->toBeFalse()
            ->and($invoice->coverFeeQuote)->toBeNull();
    });

    it('reads the flag off the list shape', function () {
        [$client] = fakeClient([jsonResponse([
            'data' => [['id' => 1, 'invoice_number' => 'INV-0001', 'status' => 'sent', 'cover_fee_required' => true]],
            'meta' => [],
        ])]);

        $page = $client->invoices->list('000010');

        expect($page->data[0])->toBeInstanceOf(InvoiceSummary::class)
            ->and($page->data[0]->coverFeeRequired)->toBeTrue();
    });
});
