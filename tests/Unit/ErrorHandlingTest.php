<?php

declare(strict_types=1);

use DimePayments\Sdk\Exceptions\AuthenticationException;
use DimePayments\Sdk\Exceptions\NotFoundException;
use DimePayments\Sdk\Exceptions\PermissionDeniedException;
use DimePayments\Sdk\Exceptions\RateLimitException;
use DimePayments\Sdk\Exceptions\ServerException;
use DimePayments\Sdk\Exceptions\ValidationException;
use Psr\Http\Message\RequestInterface;

it('maps 400 with errors to a ValidationException', function () {
    [$client] = fakeClient([
        jsonResponse(['errors' => [
            'data.amount' => ['The data.amount field must be greater than 0.'],
        ]], 400),
    ]);

    try {
        $client->transactions->chargeCard('000010', ['amount' => 0]);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->getStatusCode())->toBe(400)
            ->and($e->getErrors())->toHaveKey('data.amount')
            ->and($e->firstError())->toBe('The data.amount field must be greater than 0.');
    }
});

it('maps a message-map (address-style) 400 to a ValidationException', function () {
    [$client] = fakeClient([
        jsonResponse(['message' => [
            'data.recipient' => ['The data.recipient field is required.'],
        ]], 400),
    ]);

    $client->transactions->show('000010', ['transaction_info_id' => 1]);
})->throws(ValidationException::class);

it('maps 401 to an AuthenticationException', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'Permission Denied.']], 401),
    ]);

    try {
        $client->transactions->show('000010', ['transaction_info_id' => 1]);
        $this->fail('Expected AuthenticationException');
    } catch (AuthenticationException $e) {
        expect($e->getMessage())->toBe('Permission Denied.');
    }
});

it('maps 403 to a PermissionDeniedException', function () {
    [$client] = fakeClient([
        jsonResponse(['message' => 'You do not have access to this company.'], 403),
    ]);

    $client->transactions->show('000010', ['transaction_info_id' => 1]);
})->throws(PermissionDeniedException::class);

it('maps 404 to a NotFoundException with the API message', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'No such Merchant']], 404),
    ]);

    try {
        $client->transactions->show('000010', ['transaction_info_id' => 1]);
        $this->fail('Expected NotFoundException');
    } catch (NotFoundException $e) {
        expect($e->getMessage())->toBe('No such Merchant');
    }
});

it('maps 429 to a RateLimitException carrying Retry-After', function () {
    [$client] = fakeClient([
        jsonResponse(['message' => 'Too Many Requests'], 429, ['Retry-After' => '17']),
    ]);

    try {
        $client->transactions->show('000010', ['transaction_info_id' => 1]);
        $this->fail('Expected RateLimitException');
    } catch (RateLimitException $e) {
        expect($e->getRetryAfter())->toBe(17);
    }
});

it('maps 500 to a ServerException', function () {
    [$client] = fakeClient([
        jsonResponse(['message' => 'Server Error'], 500),
    ]);

    $client->transactions->show('000010', ['transaction_info_id' => 1]);
})->throws(ServerException::class);

it('sends the bearer token and JSON headers', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'ok']]),
    ]);

    $client->transactions->void('000010', 'CC', 1);

    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    expect($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getUri()->getPath())->toBe('/api/transaction/void');
});
