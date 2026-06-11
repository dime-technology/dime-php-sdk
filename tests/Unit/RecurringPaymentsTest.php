<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\RecurringPayment;
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
