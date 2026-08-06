<?php

declare(strict_types=1);

namespace DimePayments\Sdk;

use Closure;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Immutable configuration for the SDK client.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://app.dimepayments.com';

    public const VERSION = '1.2.0';

    /**
     * @param  string  $token  Sanctum personal access token (sent as a Bearer token).
     * @param  string  $baseUrl  The Dime application base URL; "/api" is appended automatically.
     * @param  float  $timeout  Per-request timeout in seconds.
     * @param  int  $maxRetries  How many times to retry transient failures (429/5xx/connection errors).
     * @param  float  $retryBaseDelay  Base seconds for exponential backoff between retries.
     * @param  GuzzleClient|null  $httpClient  Optional custom Guzzle client (primarily for testing).
     * @param  (Closure(float): void)|null  $sleeper  Optional sleep override (primarily for testing).
     */
    public function __construct(
        public readonly string $token,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly float $timeout = 30.0,
        public readonly int $maxRetries = 2,
        public readonly float $retryBaseDelay = 0.5,
        public readonly ?GuzzleClient $httpClient = null,
        public readonly ?Closure $sleeper = null,
    ) {}

    /**
     * Base URI for all requests, including the trailing "/api/" path.
     */
    public function baseUri(): string
    {
        return rtrim($this->baseUrl, '/').'/api/';
    }

    public function userAgent(): string
    {
        return 'dime-php-sdk/'.self::VERSION.' (php/'.PHP_VERSION.')';
    }
}
