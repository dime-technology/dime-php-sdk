<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

/**
 * Thrown for any other non-2xx response not covered by a more specific
 * exception (e.g. a 400 carrying an application message rather than
 * validation errors).
 */
class ApiException extends DimeException {}
