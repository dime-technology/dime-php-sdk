<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

use Throwable;

/**
 * A capture the API refused, with the reason as a machine-readable code.
 *
 * `errorCode` is one of `not_found`, `already_captured`, `voided`, `refunded`,
 * `authorization_declined`, `not_an_authorization`,
 * `amount_exceeds_authorization` or `processor_declined`; an unfamiliar code
 * from a newer API is passed through as given. Several codes share one
 * message ("Transaction is not an open authorization."), so branch on the
 * code, not the message.
 *
 * `reason` is set on `processor_declined` only: the explanation given when the
 * capture was refused. An expired authorization arrives this way too, since an
 * issuer drops an uncaptured hold without notice.
 *
 * Nothing was captured. Field validation still throws ValidationException.
 */
class CaptureException extends ApiException
{
    /**
     * @param  array<string, mixed>  $responseBody
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly ?string $reason = null,
        ?int $statusCode = null,
        array $responseBody = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
