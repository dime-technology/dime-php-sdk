<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\RecurringPayment;
use DimePayments\Sdk\DataObjects\RecurringPaymentItem;
use Psr\Http\Message\RequestInterface;

it('creates a recurring payment and returns a typed object', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'id' => 99,
            'name' => 'Monthly box',
            'amount' => '25.00',
            'recurrence_schedule' => 'monthly',
            'status' => 'active',
            'payment_method' => ['id' => 7, 'type' => 'CC', 'last_four' => '4242'],
            'shipping_address' => ['city' => 'Boulder', 'state' => 'CO', 'zip' => '80302'],
        ]]),
    ]);

    $payment = $client->recurringPayments->create('000010', [
        'name' => 'Monthly box',
        'amount' => '25.00',
        'start_date' => '2026-01-01',
        'recurrence_schedule' => 'monthly',
        'payment_method' => 7,
        'customer_uuid' => 'abc-123',
        'shipping_address' => ['city' => 'Boulder'],
    ]);

    expect($payment)->toBeInstanceOf(RecurringPayment::class)
        ->and($payment->status)->toBe('active')
        ->and($payment->paymentMethod->lastFour)->toBe('4242')
        ->and($payment->shippingAddress->city)->toBe('Boulder');

    expect(sentJson($history))->toBe([
        'data' => [
            'sid' => '000010',
            'name' => 'Monthly box',
            'amount' => '25.00',
            'start_date' => '2026-01-01',
            'recurrence_schedule' => 'monthly',
            'payment_method' => 7,
            'customer_uuid' => 'abc-123',
            'shipping_address' => ['city' => 'Boulder'],
        ],
    ]);
});

it('pauses with a pause_until_date under data', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'status' => 'paused', 'paused_until_date' => '2026-03-01']]),
    ]);

    $payment = $client->recurringPayments->pause('000010', 99, '2026-03-01');

    expect($payment->status)->toBe('paused')
        ->and($payment->pausedUntilDate)->toBe('2026-03-01');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99, 'pause_until_date' => '2026-03-01'],
    ]);
});

it('omits pause_until_date when null', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'status' => 'paused']]),
    ]);

    $client->recurringPayments->pause('000010', 99);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99],
    ]);
});

it('cancels via PATCH', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'status' => 'cancelled']]),
    ]);

    $payment = $client->recurringPayments->cancel('000010', 99);

    expect($payment->status)->toBe('cancelled');

    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('PATCH');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99],
    ]);
});

it('activates via PATCH', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'status' => 'active']]),
    ]);

    $payment = $client->recurringPayments->activate('000010', 99);

    expect($payment->status)->toBe('active');

    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('PATCH');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99],
    ]);
});

it('deletes and returns a message result', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'Recurring payment deleted.']]),
    ]);

    $result = $client->recurringPayments->delete('000010', 99);

    expect($result)->toBeInstanceOf(MessageResult::class)
        ->and($result->message)->toBe('Recurring payment deleted.');

    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99],
    ]);
});

it('reads the pinned item and the covered fee', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'id' => 99,
            'amount' => '25.75',
            'cover_processing_amount' => '0.75',
            'item' => ['id' => 42, 'name' => 'Building Fund'],
            'payment_method' => ['id' => 7, 'type' => 'cc'],
            'shipping_address' => [],
        ]]),
    ]);

    $payment = $client->recurringPayments->show('000010', 99);

    expect($payment->coverProcessingAmount)->toBe('0.75')
        ->and($payment->item)->toBeInstanceOf(RecurringPaymentItem::class)
        ->and($payment->item?->id)->toBe(42)
        ->and($payment->item?->name)->toBe('Building Fund');
});

it('reads an unpinned payment as a null item', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'cover_processing_amount' => null, 'item' => null]]),
    ]);

    $payment = $client->recurringPayments->show('000010', 99);

    expect($payment->item)->toBeNull()
        ->and($payment->coverProcessingAmount)->toBeNull();
});

it('keeps an item id whose name is not disclosed', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'item' => ['id' => 42, 'name' => null]]]),
    ]);

    $payment = $client->recurringPayments->show('000010', 99);

    expect($payment->item?->id)->toBe(42)
        ->and($payment->item?->name)->toBeNull();
});

it('sends item_id and customer_covers_fee on create', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'item' => ['id' => 42, 'name' => 'Building Fund']]]),
    ]);

    $client->recurringPayments->create('000010', [
        'name' => 'Monthly gift',
        'amount' => '25.00',
        'start_date' => '2026-01-01',
        'recurrence_schedule' => 'Monthly',
        'payment_method' => 7,
        'customer_uuid' => 'abc-123',
        'customer_covers_fee' => true,
        'item_id' => 42,
    ]);

    expect(sentJson($history)['data'])->toMatchArray(['customer_covers_fee' => true, 'item_id' => 42]);
});

it('sends a null item_id on edit to unpin, and still drops other nulls', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'item' => null]]),
    ]);

    $payment = $client->recurringPayments->edit('000010', 99, ['item_id' => null, 'name' => null]);

    expect($payment->item)->toBeNull();
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99, 'item_id' => null],
    ]);
});

it('leaves item_id out of an edit that does not mention it', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['id' => 99, 'amount' => '30.00']]),
    ]);

    $client->recurringPayments->edit('000010', 99, ['amount' => '30.00']);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'recurring_payment_id' => 99, 'amount' => '30.00'],
    ]);
});
