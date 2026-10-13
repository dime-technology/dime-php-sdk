<?php

declare(strict_types=1);

use DimePayments\Sdk\Client;
use DimePayments\Sdk\Config;
use DimePayments\Sdk\Exceptions\ApiException;
use DimePayments\Sdk\Exceptions\IdempotencyException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

/**
 * The `Idempotency-Key` header recorded on request $index, or null when none was sent.
 *
 * @param  ArrayObject<int, array<string, mixed>>  $history
 */
function sentIdempotencyKey(ArrayObject $history, int $index = 0): ?string
{
    /** @var RequestInterface $request */
    $request = $history[$index]['request'];

    return $request->hasHeader('Idempotency-Key') ? $request->getHeaderLine('Idempotency-Key') : null;
}

$transactionResponse = fn () => jsonResponse(['data' => ['transaction_number' => '1', 'pending' => false]]);
$messageResponse = fn () => jsonResponse(['data' => ['message' => 'ok']]);

it('sends a generated key on every call that moves money', function (Closure $call, Closure $response) {
    [$client, $history] = fakeClient([$response()]);

    $call($client);

    expect(sentIdempotencyKey($history))
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
})->with([
    'chargeCard' => [fn (Client $c) => $c->transactions->chargeCard('000010', ['amount' => 1, 'token' => 't']), $transactionResponse],
    'chargeCardToken' => [fn (Client $c) => $c->transactions->chargeCardToken('000010', ['amount' => 1, 'token' => 't']), $transactionResponse],
    'chargeAch' => [fn (Client $c) => $c->transactions->chargeAch('000010', ['amount' => 1]), $transactionResponse],
    'authorize' => [fn (Client $c) => $c->transactions->authorize('000010', ['amount' => 1, 'token' => 't']), $transactionResponse],
    'capture' => [fn (Client $c) => $c->transactions->capture('000010', 123), $messageResponse],
    'refund' => [fn (Client $c) => $c->transactions->refund('000010', ['amount' => 1, 'transaction_info_id' => 1]), $messageResponse],
    'void' => [fn (Client $c) => $c->transactions->void('000010', 'CC', 123), $messageResponse],
]);

it('uses the caller key as given', function () use ($transactionResponse, $messageResponse) {
    [$client, $history] = fakeClient([$transactionResponse(), $messageResponse(), $messageResponse()]);

    $client->transactions->chargeCard('000010', ['amount' => 1, 'token' => 't'], idempotencyKey: 'order-1234');
    $client->transactions->capture('000010', 123, 50, 'capture-1');
    $client->transactions->void('000010', 'CC', 123, idempotencyKey: 'void-1');

    expect(sentIdempotencyKey($history, 0))->toBe('order-1234')
        ->and(sentIdempotencyKey($history, 1))->toBe('capture-1')
        ->and(sentIdempotencyKey($history, 2))->toBe('void-1')
        ->and(sentJson($history, 1))->toBe(['data' => ['sid' => '000010', 'transaction_id' => 123, 'amount' => 50]]);
});

it('gives each call its own key', function () use ($transactionResponse) {
    [$client, $history] = fakeClient([$transactionResponse(), $transactionResponse()]);

    $client->transactions->chargeCard('000010', ['amount' => 1, 'token' => 't']);
    $client->transactions->chargeCard('000010', ['amount' => 1, 'token' => 't']);

    expect(sentIdempotencyKey($history, 0))->not->toBe(sentIdempotencyKey($history, 1));
});

it('sends the same key on the SDK own retries', function () use ($transactionResponse) {
    $mock = new MockHandler([
        jsonResponse(['message' => 'Server Error'], 503),
        jsonResponse(['message' => 'Server Error'], 500),
        $transactionResponse(),
    ]);
    $history = new ArrayObject;
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(new Config(
        token: 'test-token',
        httpClient: new GuzzleClient(['handler' => $stack, 'http_errors' => false, 'base_uri' => 'https://app.dimepayments.com/api/']),
        maxRetries: 2,
        sleeper: function (float $seconds): void {},
    ));

    $client->transactions->chargeCard('000010', ['amount' => 1, 'token' => 't']);

    expect($history)->toHaveCount(3)
        ->and(sentIdempotencyKey($history, 1))->toBe(sentIdempotencyKey($history, 0))
        ->and(sentIdempotencyKey($history, 2))->toBe(sentIdempotencyKey($history, 0));
});

it('sends no key on calls that do not move money', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['transaction_number' => '1', 'pending' => false]]),
        jsonResponse(['data' => ['token' => 'tok_1']]),
    ]);

    $client->transactions->show('000010', ['transaction_info_id' => 1]);
    $client->transactions->tokenizeCard('000010', ['cardholder_name' => 'A', 'card_number' => '4111', 'expiration_date' => '01/30']);

    expect(sentIdempotencyKey($history, 0))->toBeNull()
        ->and(sentIdempotencyKey($history, 1))->toBeNull();
});

it('throws IdempotencyException for a key still in use or reused for another request', function (int $status, string $message) {
    [$client] = fakeClient([jsonResponse(['data' => ['message' => $message]], $status)]);

    expect(fn () => $client->transactions->chargeCard('000010', ['amount' => 1, 'token' => 't'], idempotencyKey: 'k'))
        ->toThrow(IdempotencyException::class, $message);
})->with([
    'still running' => [409, 'A request with this Idempotency-Key is still in progress, or did not finish. Try again shortly; if this persists, check the transaction list before retrying with a new key.'],
    'different request' => [422, 'This Idempotency-Key was already used for a different request. Use a new key for each new request.'],
]);

it('leaves any other 409 as an ApiException', function () {
    [$client] = fakeClient([jsonResponse(['data' => ['message' => 'You already have one or more merchants that look like this one.']], 409)]);

    try {
        $client->merchants->create(['name' => 'Acme']);
        $this->fail('Expected an exception');
    } catch (ApiException $e) {
        expect($e)->not->toBeInstanceOf(IdempotencyException::class)
            ->and($e->getStatusCode())->toBe(409);
    }
});
