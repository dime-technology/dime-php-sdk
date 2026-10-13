<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

/**
 * The API refused a request because of its `Idempotency-Key`: HTTP 409 while a
 * request with the same key is still running, or HTTP 422 when the key was
 * already used for a different request. Nothing was processed. On a 409, wait
 * and retry with the same key; on a 422, the request needs a new key.
 */
class IdempotencyException extends ApiException {}
