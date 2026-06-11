<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A hosted form link returned by the merchant get-form-link endpoint.
 */
final class FormLink
{
    public function __construct(public readonly ?string $link) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(link: Arr::string($data, 'link'));
    }
}
