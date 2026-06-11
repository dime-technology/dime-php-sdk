<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

/**
 * Thrown for HTTP 401 responses: a missing/invalid token, or a token whose
 * abilities do not permit the requested operation ("Permission Denied.").
 */
class AuthenticationException extends DimeException {}
