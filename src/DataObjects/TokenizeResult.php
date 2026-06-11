<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The result of tokenizing a card: a single reusable token string.
 */
final class TokenizeResult
{
    public function __construct(public readonly ?string $token) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(token: Arr::string($data, 'token'));
    }
}
