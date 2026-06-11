<?php

declare(strict_types=1);

use DimePayments\Sdk\Client;
use DimePayments\Sdk\Config;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Build a Client backed by a mocked HTTP handler.
 *
 * @param  array<int, Response>  $responses  Responses to return, in order.
 * @return array{0: Client, 1: ArrayObject<int, array<string, mixed>>} The client and the
 *                                                                     recorded request history (each entry has a 'request' key). The history is an
 *                                                                     ArrayObject so the middleware's appends remain visible to the caller after requests run.
 */
function fakeClient(array $responses): array
{
    $mock = new MockHandler($responses);
    /** @var ArrayObject<int, array<string, mixed>> $history */
    $history = new ArrayObject;
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $guzzle = new GuzzleClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => 'https://app.dimepayments.com/api/',
    ]);

    $config = new Config(
        token: 'test-token',
        httpClient: $guzzle,
        maxRetries: 0,
        sleeper: function (float $seconds): void {},
    );

    return [new Client($config), $history];
}

/**
 * Convenience: a JSON Response.
 *
 * @param  array<string, mixed>  $body
 * @param  array<string, string>  $headers
 */
function jsonResponse(array $body, int $status = 200, array $headers = []): Response
{
    return new Response($status, ['Content-Type' => 'application/json'] + $headers, (string) json_encode($body));
}

/**
 * Decode the JSON body that was sent on the recorded request at $index.
 *
 * @param  ArrayObject<int, array<string, mixed>>  $history
 * @return array<string, mixed>
 */
function sentJson(ArrayObject $history, int $index = 0): array
{
    /** @var RequestInterface $request */
    $request = $history[$index]['request'];

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode((string) $request->getBody(), true) ?: [];

    return $decoded;
}
