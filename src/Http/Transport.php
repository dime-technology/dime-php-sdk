<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Http;

use DimePayments\Sdk\Config;
use DimePayments\Sdk\Exceptions\ConnectionException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin wrapper over the underlying HTTP client: builds authenticated requests,
 * applies retry/backoff for transient failures, decodes JSON, and delegates
 * non-2xx responses to {@see ErrorHandler}.
 *
 * Note: the Dime API accepts (and for read endpoints, expects) a JSON body on
 * GET requests, so this transport always sends the payload as a JSON body
 * regardless of HTTP method. The one exception is document upload, which is
 * `multipart/form-data` and goes through {@see multipart()}.
 */
final class Transport
{
    private readonly GuzzleClient $client;

    public function __construct(private readonly Config $config)
    {
        $this->client = $config->httpClient ?? new GuzzleClient([
            'base_uri' => $config->baseUri(),
            'timeout' => $config->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * Execute a request and return the decoded JSON body.
     *
     * @param  array<string, mixed>  $body  Request payload (sent as a JSON body).
     * @param  array<string, scalar>  $query  Query string parameters (e.g. pagination cursor).
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $options = [
            'headers' => $this->headers() + ['Content-Type' => 'application/json'],
        ];

        if ($body !== []) {
            $options['json'] = $body;
        }

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->execute($method, $path, $options);
    }

    /**
     * Execute a `multipart/form-data` request (used for file uploads) and return
     * the decoded JSON body. The multipart boundary sets the Content-Type, so no
     * JSON header is sent.
     *
     * @param  array<int, array{name: string, contents: mixed, filename?: string}>  $multipart  Guzzle multipart parts.
     * @return array<string, mixed>
     */
    public function multipart(string $method, string $path, array $multipart): array
    {
        return $this->execute($method, $path, [
            'headers' => $this->headers(),
            'multipart' => $multipart,
        ]);
    }

    /**
     * Headers sent on every request regardless of body encoding.
     *
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->config->token,
            'Accept' => 'application/json',
            'User-Agent' => $this->config->userAgent(),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function execute(string $method, string $path, array $options): array
    {
        $response = $this->send($method, ltrim($path, '/'), $options);
        $status = $response->getStatusCode();
        $decoded = $this->decode($response);

        if ($status < 200 || $status >= 300) {
            ErrorHandler::throw($status, $decoded, self::retryAfter($response));
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function send(string $method, string $path, array $options): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $this->client->request($method, $path, $options);
            } catch (ConnectException $e) {
                if ($attempt < $this->config->maxRetries) {
                    $this->sleep($attempt, null);
                    $attempt++;

                    continue;
                }

                throw new ConnectionException(
                    'Could not reach the Dime API: '.$e->getMessage(),
                    previous: $e,
                );
            } catch (GuzzleException $e) {
                throw new ConnectionException(
                    'HTTP transport error: '.$e->getMessage(),
                    previous: $e,
                );
            }

            $status = $response->getStatusCode();

            if (self::isRetryable($status) && $attempt < $this->config->maxRetries) {
                $this->sleep($attempt, self::retryAfter($response));
                $attempt++;

                continue;
            }

            return $response;
        }
    }

    private static function isRetryable(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    private static function retryAfter(ResponseInterface $response): ?int
    {
        $header = $response->getHeaderLine('Retry-After');

        return is_numeric($header) ? (int) $header : null;
    }

    private function sleep(int $attempt, ?int $retryAfter): void
    {
        $seconds = $retryAfter ?? min(2 ** $attempt * $this->config->retryBaseDelay, 30);

        if ($this->config->sleeper !== null) {
            ($this->config->sleeper)($seconds);

            return;
        }

        usleep((int) ($seconds * 1_000_000));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $contents = (string) $response->getBody();

        if ($contents === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : ['message' => $contents];
    }
}
