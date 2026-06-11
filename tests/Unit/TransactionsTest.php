<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Transaction;

it('charges a card and returns a typed transaction', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'transaction_type' => 'Credit Card',
            'transaction_status' => 'Success',
            'transaction_number' => '1234567890',
            'amount' => '49.99',
            'pending' => true,
            'customer_uuid' => 'abc-123',
            'billing_address' => ['first_name' => 'John', 'last_name' => 'Doe', 'zip' => '30009'],
            'shippingAddress' => ['addr1' => '12 Street Ave', 'city' => 'Boulder', 'state' => 'CO', 'zip' => '80302'],
        ]]),
    ]);

    $transaction = $client->transactions->chargeCard('000010', [
        'amount' => 49.99,
        'token' => 'tok_abc123',
    ]);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->transactionStatus)->toBe('Success')
        ->and($transaction->amount)->toBe('49.99')
        ->and($transaction->pending)->toBeTrue()
        ->and($transaction->billingAddress->firstName)->toBe('John')
        ->and($transaction->shippingAddress->city)->toBe('Boulder');

    // Request was wrapped in the data envelope with the sid merged in.
    $sent = sentJson($history);
    expect($sent)->toBe([
        'data' => ['sid' => '000010', 'amount' => 49.99, 'token' => 'tok_abc123'],
    ]);
});

it('reads shipping from the snake_case key too', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'transaction_number' => '1',
            'pending' => false,
            'shipping_address' => ['city' => 'Atlanta'],
        ]]),
    ]);

    $transaction = $client->transactions->show('000010', ['transaction_info_id' => 1]);

    expect($transaction->shippingAddress->city)->toBe('Atlanta');
});

it('paginates transactions across cursors', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [['transaction_number' => '1', 'pending' => false]],
            'links' => ['next' => 'http://x/api/transactions?cursor=page2'],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['transaction_number' => '2', 'pending' => false]],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->transactions->list('000010');

    expect($page->count())->toBe(1)
        ->and($page->hasMore())->toBeTrue()
        ->and($page->perPage)->toBe(500);

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[0]->transactionNumber)->toBe('1')
        ->and($all[1]->transactionNumber)->toBe('2');
});

it('voids a transaction', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'Transaction voided successfully.']]),
    ]);

    $result = $client->transactions->void('000010', 'CC', 42);

    expect($result->message)->toBe('Transaction voided successfully.');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'transaction_type' => 'CC', 'transaction_id' => 42],
    ]);
});
