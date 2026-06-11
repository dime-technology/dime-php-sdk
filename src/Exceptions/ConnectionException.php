<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

/**
 * Thrown when the request never produced an HTTP response (DNS failure,
 * connection timeout, TLS error, etc.).
 */
class ConnectionException extends DimeException {}
