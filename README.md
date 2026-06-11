# Dime Payments PHP SDK

A typed PHP client for the [Dime Payments](https://dimepayments.com) API. It wraps the HTTP
contract — authentication, the `data`/`filters` request envelope, cursor pagination, and error
handling — behind small, predictable resource methods that return readonly data objects.

```php
$dime = new \DimePayments\Sdk\Client('your-api-token');

$transaction = $dime->transactions->chargeCard('000010', [
    'amount' => 49.99,
    'token'  => 'tok_abc123',
]);

echo $transaction->transactionStatus;  // "Success"
```

## Requirements

- PHP 8.3+
- A Dime API token (a Laravel Sanctum personal access token). Tokens are minted inside the Dime
  application/admin, not via this SDK, and carry abilities (e.g. `transaction:charge-card-token`,
  `customer:read`, `merchant:update`) that gate which calls succeed.

## Installation

```bash
composer require dime-technology/dime-php-sdk
```

## Configuration

The simplest setup needs only a token (the base URL defaults to `https://app.dimepayments.com`):

```php
$dime = new \DimePayments\Sdk\Client('your-api-token');
```

Point it at another environment by passing a base URL, or use `Config` for full control
(timeout, retries, a custom Guzzle client):

```php
use DimePayments\Sdk\Client;
use DimePayments\Sdk\Config;

$dime = new Client('your-api-token', 'https://staging.dimepayments.com');

$dime = new Client(new Config(
    token:      'your-api-token',
    baseUrl:    'https://app.dimepayments.com',
    timeout:    30.0,
    maxRetries: 2,      // retries 429 / 5xx / connection errors with backoff
));
```

The SDK sends `Authorization: Bearer <token>` and JSON headers on every request. Transient
failures (HTTP 429 and 5xx, connection errors) are retried with exponential backoff, honoring the
`Retry-After` header when present.

## Resources

Every resource hangs off the client as a readonly property. The merchant `sid` is always passed
explicitly; remaining fields go in an `$attributes` array (and lookups, where the API expects them,
in a `$filters` array). All amounts are returned as strings to avoid float rounding.

| Property                  | Endpoints                                                        |
| ------------------------- | ---------------------------------------------------------------- |
| `$dime->transactions`     | charge card/ACH, tokenize, refund, void, show, list              |
| `$dime->customers`        | list, show, create, update, delete                               |
| `$dime->paymentMethods`   | list, show, create, update, delete                               |
| `$dime->merchants`        | list, show, create, update, get onboarding form link            |
| `$dime->addresses`        | list, show, create, update, delete                               |
| `$dime->deposits`         | list, list-with-transactions, show                               |
| `$dime->recurringPayments`| list, show, create, edit, pause, cancel, activate, delete        |

### Transactions

```php
// Charge a stored token
$txn = $dime->transactions->chargeCard('000010', [
    'amount' => 100.00,
    'token'  => 'tok_abc123',
    'email'  => 'customer@example.com',
]);

// Charge raw card details (merchant must be PCI compliant)
$txn = $dime->transactions->chargeCard('000010', [
    'amount'          => 100.00,
    'cardholder_name' => 'John Doe',
    'card_number'     => '4111111111111111',
    'expiration_date' => '01/2027',
    'cvv'             => '123',
    'billing_address' => ['zip' => '30009'],
]);

// ACH
$txn = $dime->transactions->chargeAch('000010', [
    'routing_number' => '123456789',
    'account_number' => '9876543210',
    'account_type'   => 'Checking',
    'account_name'   => 'John Doe',
    'amount'         => 75.00,
]);

// Tokenize without charging
$token = $dime->transactions->tokenizeCard('000010', [
    'cardholder_name' => 'John Doe',
    'card_number'     => '4111111111111111',
    'expiration_date' => '01/2027',
    'billing_address' => ['zip' => '30009'],
])->token;

// Refund / void
$dime->transactions->refund('000010', ['amount' => 25.00, 'transaction_info_id' => 123456]);
$dime->transactions->void('000010', 'CC', 123456);

// Read
$txn = $dime->transactions->show('000010', ['transaction_info_id' => 123456]);
```

### Customers, payment methods, addresses

```php
$customer = $dime->customers->create('000010', [
    'first_name' => 'Jane',
    'last_name'  => 'Doe',
    'email'      => 'jane@example.com',
]);

$customer = $dime->customers->show('000010', ['uuid' => $customer->uuid]);

$pm = $dime->paymentMethods->create('000010', [
    'uuid'               => $customer->uuid,
    'type'               => 'cc',
    'cc_name_on_card'    => 'Jane Doe',
    'cc_number'          => '4111111111111111',
    'cc_expiration_date' => '01/2027',
    'cc_cvv'             => '123',
    'cc_brand'           => 'Visa',
    'addr1'              => '123 Main St',
    'city'               => 'Alpharetta',
    'state'              => 'GA',
    'zip'                => '30009',
    'default'            => true,
]);

$address = $dime->addresses->create('000010', $customer->uuid, [
    'recipient' => 'Jane Doe',
    'line_one'  => '123 Main St',
    'city'      => 'Atlanta',
    'state'     => 'GA',
    'zip'       => '30301',
]);
```

### Recurring payments

```php
$rp = $dime->recurringPayments->create('000010', [
    'name'                => 'Monthly donation',
    'amount'              => 25.00,
    'start_date'          => '2026-07-01 00:00:00',
    'recurrence_schedule' => 'Monthly',
    'payment_method'      => $pm->id,
    'customer_uuid'       => $customer->uuid,
]);

$dime->recurringPayments->pause('000010', $rp->id, '2026-09-01 00:00:00');
$dime->recurringPayments->activate('000010', $rp->id);
$dime->recurringPayments->cancel('000010', $rp->id);
```

## Pagination

List endpoints return a `CursorPage`. Iterate one page, walk pages manually, or stream every item
across all pages with `autoPaging()`:

```php
$page = $dime->transactions->list('000010', [
    'start_date' => '2026-01-01 00:00:00',
    'end_date'   => '2026-01-31 23:59:59',
]);

foreach ($page as $txn) {
    // first page only
}

if ($page->hasMore()) {
    $next = $page->next();
}

// Every transaction across every page (fetches lazily as you iterate)
foreach ($dime->transactions->list('000010')->autoPaging() as $txn) {
    echo $txn->transactionNumber, PHP_EOL;
}
```

## Error handling

Every failure throws a `DimePayments\Sdk\Exceptions\DimeException` subclass. Catch the base type, or
a specific one:

```php
use DimePayments\Sdk\Exceptions\DimeException;
use DimePayments\Sdk\Exceptions\ValidationException;
use DimePayments\Sdk\Exceptions\RateLimitException;

try {
    $dime->transactions->chargeCard('000010', ['amount' => 0]);
} catch (ValidationException $e) {
    $e->getErrors();     // ['data.amount' => ['The data.amount field must be greater than 0.']]
    $e->firstError();
} catch (RateLimitException $e) {
    sleep($e->getRetryAfter() ?? 1);
} catch (DimeException $e) {
    $e->getStatusCode();   // HTTP status
    $e->getResponseBody(); // decoded API body
}
```

| Exception                    | When                                                       |
| ---------------------------- | ---------------------------------------------------------- |
| `ValidationException`        | HTTP 400/422 with field errors                             |
| `AuthenticationException`    | HTTP 401 (missing/invalid token, or insufficient ability)  |
| `PermissionDeniedException`  | HTTP 403 (`belongs-to-company` guard)                      |
| `NotFoundException`          | HTTP 404                                                    |
| `RateLimitException`         | HTTP 429 (carries `Retry-After`)                           |
| `ServerException`            | HTTP 5xx                                                    |
| `ConnectionException`        | No HTTP response (DNS, timeout, TLS)                        |
| `ApiException`               | Any other non-2xx                                          |

## Notes

- **GET requests carry a JSON body.** The Dime API expects read parameters in the request body even
  for `GET` endpoints; the SDK handles this for you.
- **No API versioning.** Endpoints live under `/api` with no version prefix.
- A handful of list endpoints (customers, merchants) return their collection without the
  `links`/`meta` block; `CursorPage` degrades gracefully (items are returned, `hasMore()` is false).

## Development

```bash
composer install
composer test      # Pest
composer analyse   # PHPStan (level 6)
composer lint      # Pint
```

## License

MIT. See [LICENSE](LICENSE).
