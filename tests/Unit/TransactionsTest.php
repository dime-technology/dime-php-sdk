<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\CaptureResult;
use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\Transaction;
use DimePayments\Sdk\Exceptions\ApiException;
use DimePayments\Sdk\Exceptions\CaptureException;
use DimePayments\Sdk\Exceptions\IdempotencyException;
use DimePayments\Sdk\Exceptions\ValidationException;

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

it('authorizes a card and returns the pending transaction', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'transaction_type' => 'Credit Card',
            'transaction_status' => 'Pending',
            'transaction_number' => '1234567890',
            'amount' => '100.50',
            'pending' => true,
        ]]),
    ]);

    $authorization = $client->transactions->authorize('000010', [
        'amount' => 100.50,
        'token' => 'tok_abc123',
    ]);

    expect($authorization)->toBeInstanceOf(Transaction::class)
        ->and($authorization->transactionStatus)->toBe('Pending')
        ->and($authorization->transactionNumber)->toBe('1234567890')
        ->and($authorization->pending)->toBeTrue();

    expect($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/transaction/authorize');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'amount' => 100.50, 'token' => 'tok_abc123'],
    ]);
});

it('captures part of an authorization and says what it took', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'message' => 'Transaction captured successfully.',
            'transaction_id' => '1234567890',
            'authorized_amount' => '120.00',
            'captured_amount' => '80.25',
            'status' => 'captured',
            'captured_at' => '2026-10-13T15:02:11-04:00',
        ]]),
    ]);

    $result = $client->transactions->capture('000010', '1234567890', 80.25);

    expect($result)->toBeInstanceOf(CaptureResult::class)
        ->toBeInstanceOf(MessageResult::class)
        ->and($result->message)->toBe('Transaction captured successfully.')
        ->and($result->transactionId)->toBe('1234567890')
        ->and($result->authorizedAmount)->toBe('120.00')
        ->and($result->capturedAmount)->toBe('80.25')
        ->and($result->status)->toBe('captured')
        ->and($result->capturedAt)->toBe('2026-10-13T15:02:11-04:00');

    expect($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/transaction/capture');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'transaction_id' => '1234567890', 'amount' => 80.25],
    ]);
});

it('leaves the capture fields null against an API that only sends a message', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'Transaction captured successfully.']]),
    ]);

    $result = $client->transactions->capture('000010', '1234567890');

    expect($result->message)->toBe('Transaction captured successfully.')
        ->and($result->capturedAmount)->toBeNull()
        ->and($result->status)->toBeNull();
});

it('throws a CaptureException with the code when a capture is refused', function (string $code, string $message) {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => $message, 'code' => $code]], 400),
    ]);

    try {
        $client->transactions->capture('000010', '1234567890');
        $this->fail('Expected a CaptureException.');
    } catch (CaptureException $e) {
        expect($e)->toBeInstanceOf(ApiException::class)
            ->and($e->errorCode)->toBe($code)
            ->and($e->getErrorCode())->toBe($code)
            ->and($e->getMessage())->toBe($message)
            ->and($e->reason)->toBeNull()
            ->and($e->getStatusCode())->toBe(400);
    }
})->with([
    'not_found' => ['not_found', 'No transaction found.'],
    'already_captured' => ['already_captured', 'Transaction is not an open authorization.'],
    'voided' => ['voided', 'Transaction is not an open authorization.'],
    'refunded' => ['refunded', 'Transaction is not an open authorization.'],
    'authorization_declined' => ['authorization_declined', 'Transaction is not an open authorization.'],
    'not_an_authorization' => ['not_an_authorization', 'Transaction is not an open authorization.'],
    'amount_exceeds_authorization' => ['amount_exceeds_authorization', 'Amount cannot be greater than the authorized amount'],
    'an unfamiliar code' => ['some_future_code', 'Something new.'],
]);

it('carries the reason on a declined capture', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'message' => 'Error processing capture',
            'code' => 'processor_declined',
            'reason' => 'Transaction rejected because the authorization has expired.',
        ]], 400),
    ]);

    try {
        $client->transactions->capture('000010', '1234567890', 50);
        $this->fail('Expected a CaptureException.');
    } catch (CaptureException $e) {
        expect($e->errorCode)->toBe('processor_declined')
            ->and($e->reason)->toBe('Transaction rejected because the authorization has expired.')
            ->and($e->getReason())->toBe('Transaction rejected because the authorization has expired.');
    }
});

it('keeps a capture validation error a ValidationException', function () {
    [$client] = fakeClient([
        jsonResponse(['errors' => ['data.transaction_id' => ['The data.transaction_id field is required.']]], 400),
    ]);

    expect(fn () => $client->transactions->capture('000010', ''))
        ->toThrow(ValidationException::class, 'The data.transaction_id field is required.');
});

it('keeps a capture refusal without a code an ApiException', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'Transaction is not an open authorization.']], 400),
    ]);

    try {
        $client->transactions->capture('000010', '1234567890');
        $this->fail('Expected an ApiException.');
    } catch (ApiException $e) {
        expect($e)->not->toBeInstanceOf(CaptureException::class);
    }
});

it('keeps an idempotency refusal on a capture an IdempotencyException', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'This Idempotency-Key was already used for a different request. Use a new key for each new request.']], 422),
    ]);

    expect(fn () => $client->transactions->capture('000010', '1234567890', idempotencyKey: 'k-1'))
        ->toThrow(IdempotencyException::class);
});

it('captures the full authorized amount when none is given', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'Transaction captured successfully.']]),
    ]);

    $client->transactions->capture('000010', 1234567890);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'transaction_id' => 1234567890],
    ]);
});
