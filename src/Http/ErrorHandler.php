<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Http;

use DimePayments\Sdk\Exceptions\ApiException;
use DimePayments\Sdk\Exceptions\AuthenticationException;
use DimePayments\Sdk\Exceptions\NotFoundException;
use DimePayments\Sdk\Exceptions\PermissionDeniedException;
use DimePayments\Sdk\Exceptions\RateLimitException;
use DimePayments\Sdk\Exceptions\ServerException;
use DimePayments\Sdk\Exceptions\ValidationException;

/**
 * Maps a non-2xx API response onto the appropriate SDK exception.
 *
 * The Dime API is not perfectly uniform: validation errors arrive as HTTP 400
 * (occasionally 422) under either an `errors` key or a `message` map, while
 * application errors arrive under `data.message`. This handler normalises all
 * of that into a typed exception hierarchy.
 */
final class ErrorHandler
{
    /**
     * @param  array<string, mixed>  $body
     */
    public static function throw(int $status, array $body, ?int $retryAfter = null): never
    {
        if (self::looksLikeValidation($status, $body)) {
            $errors = self::extractErrors($body);

            throw new ValidationException(
                message: self::firstMessage($errors) ?? 'The given data was invalid.',
                errors: $errors,
                statusCode: $status,
                responseBody: $body,
            );
        }

        $message = self::extractMessage($body) ?? "Dime API request failed with status {$status}.";

        throw match (true) {
            $status === 401 => new AuthenticationException($message, $status, $body),
            $status === 403 => new PermissionDeniedException($message, $status, $body),
            $status === 404 => new NotFoundException($message, $status, $body),
            $status === 429 => new RateLimitException($message, $retryAfter, $status, $body),
            $status >= 500 => new ServerException($message, $status, $body),
            default => new ApiException($message, $status, $body),
        };
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function looksLikeValidation(int $status, array $body): bool
    {
        if (isset($body['errors']) && is_array($body['errors'])) {
            return true;
        }

        // Some endpoints (e.g. address) return validation errors as a `message` map.
        return in_array($status, [400, 422], true)
            && isset($body['message'])
            && is_array($body['message']);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, array<int, string>>
     */
    private static function extractErrors(array $body): array
    {
        /** @var mixed $raw */
        $raw = $body['errors'] ?? $body['message'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        $errors = [];

        foreach ($raw as $field => $messages) {
            $errors[(string) $field] = array_values(array_map(
                static fn ($message): string => (string) $message,
                is_array($messages) ? $messages : [$messages],
            ));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function extractMessage(array $body): ?string
    {
        if (isset($body['data']['message']) && is_string($body['data']['message'])) {
            return $body['data']['message'];
        }

        if (isset($body['message']) && is_string($body['message'])) {
            return $body['message'];
        }

        return null;
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private static function firstMessage(array $errors): ?string
    {
        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                return $message;
            }
        }

        return null;
    }
}
