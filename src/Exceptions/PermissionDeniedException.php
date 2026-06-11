<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

/**
 * Thrown for HTTP 403 responses: the authenticated user is not permitted to
 * act on the requested company (the `belongs-to-company` guard).
 */
class PermissionDeniedException extends DimeException {}
