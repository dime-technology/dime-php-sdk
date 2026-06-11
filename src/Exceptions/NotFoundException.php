<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

/**
 * Thrown for HTTP 404 responses (e.g. "No such Merchant", "Customer not found").
 */
class NotFoundException extends DimeException {}
