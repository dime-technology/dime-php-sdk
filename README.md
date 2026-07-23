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
| `$dime->invoices`         | list, show, create, update, delete, send, markSent, void, duplicate, pay, link, addLineItem, updateLineItem, deleteLineItem, listItems, createItem, listRecurring, showRecurring, createRecurring, cancelRecurring |
| `$dime->subscriptionPlans`| list, show, create, update, delete, publish, archive, unarchive, subscribe |
| `$dime->subscriptions`    | list, show, pause, resume, cancel                                |

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

### Invoices

Invoices are scoped to a merchant `sid` and built from line items that each reference a merchant
item (a fund or designation). Draft invoices can be edited; once sent they are locked.

Identify the customer with `customer_uuid` — the same uuid every other resource uses, and the only
identifier the customer endpoints return. `customer_id` is still accepted for older integrations.

**Statuses.** `$invoice->status` is one of `draft`, `sent`, `viewed`, `partially_paid`, `paid`,
`void` or `refunded` — lowercase. To detect settlement, compare against `paid`; `partially_paid`
means a payment landed but a balance remains, which `$invoice->balance` reports.

`paid` is not always final. If the customer's bank returns an ACH payment, the invoice is reopened: it goes
back to `partially_paid`, `viewed` or `sent`, with `$invoice->amountPaid` and `$invoice->balance` updated, and
an `invoice_payment_returned` webhook fires. Re-read the invoice rather than caching a `paid` status forever.

There is no `overdue` status. Being overdue is a property of an open invoice past its due date, so
it reads as `$invoice->isOverdue`. `overdue` and `all` are accepted as *filter* values on `list()`
alongside the real statuses.

```php
// Look up (or create) the items a line can reference
$items = $dime->invoices->listItems('000010');
$item  = $dime->invoices->createItem('000010', [
    'name'           => 'Consulting',
    'price'          => 125,
    'tax_deductible' => false,
]);

// Create a draft invoice with one or more line items
$invoice = $dime->invoices->create('000010', [
    'customer_uuid'  => $customer->uuid,
    'customer_name'  => 'Jane Doe',
    'customer_email' => 'jane@example.com',
    'payment_terms'  => 'net_15', // due_on_receipt | net_15 | net_30 | net_60
    'lines'          => [
        ['item_id' => $item->id, 'name' => 'Consulting', 'description' => '2 hours', 'quantity' => 2, 'unit_price' => 125],
    ],
]);

// Tweak the draft's line items (each returns the refreshed invoice)
$invoice = $dime->invoices->addLineItem('000010', $invoice->id, ['item_id' => $item->id, 'name' => 'Setup', 'quantity' => 1, 'unit_price' => 50]);
$invoice = $dime->invoices->updateLineItem('000010', $invoice->id, $invoice->items[0]->id, ['quantity' => 3]);
$invoice = $dime->invoices->deleteLineItem('000010', $invoice->id, $invoice->items[0]->id);

// Email it to the customer, or activate the pay link without emailing
$dime->invoices->send('000010', $invoice->id);
$dime->invoices->markSent('000010', $invoice->id);

// Share the public pay link
$link = $dime->invoices->link('000010', $invoice->id);
echo $link->publicUrl;

// Take payment against the balance. payment_type is required; omit amount to
// pay the full balance.
$dime->invoices->pay('000010', $invoice->id, [
    'payment_type' => 'cc', // cc | ach
    'token'        => $pm->token,
    'amount'       => 125.00,
]);

$dime->invoices->void('000010', $invoice->id);
```

#### Making the customer cover processing fees

Set `cover_fee_required` and the customer must pay the processing fee — it is not an optional
checkbox at checkout. The fee is **not** a line item and is **not** part of `total`: the merchant is
still owed `total`, and the fee is added on top of whatever the customer pays.

Card and ACH rates differ, so the charge depends on how the customer pays. `coverFeeQuote` gives you
both, quoted against the outstanding balance:

```php
$invoice = $dime->invoices->create('000010', [
    'customer_uuid'      => $customer->uuid,
    'customer_name'      => 'Jane Doe',
    'customer_email'     => 'jane@example.com',
    'payment_terms'      => 'net_15',
    'cover_fee_required' => true, // omit to inherit the merchant's invoice setting
    'lines'              => [
        ['item_id' => $item->id, 'name' => 'Consulting', 'quantity' => 1, 'unit_price' => 100],
    ],
]);

$invoice->total;                   // "100.00" — what the merchant is owed
$invoice->coverFeeQuote->ccTotal;  // "104.32" — charged if they pay by card
$invoice->coverFeeQuote->achTotal; // "101.26" — charged if they pay by bank
```

The card figure is the higher of the two and is what the invoice and its emails lead with. A partial
payment re-quotes the fee against the partial amount, so treat the quote as "settling in full today"
rather than a fixed charge. `coverFeeQuote` is `null` when no fee is required.

To reconcile a payment, remember that `amount` was credited to the invoice and `coverFee` was
charged on top of it:

```php
$payment = $invoice->payments[0];
$payment->amount;   // "100.00" — applied to the balance
$payment->coverFee; // "4.32"   — the fee the customer also paid
// The customer was charged amount + coverFee.
```

`pay()` behaves the same way: the fee for the `payment_type` you pass is added to `amount`, so the
card or bank account is debited more than the invoice is credited.

Recurring invoices are templates that emit an invoice on a schedule. `cover_fee_required` is copied
onto every invoice the template generates:

```php
$template = $dime->invoices->createRecurring('000010', [
    'customer_uuid'        => $customer->uuid,
    'payment_terms'        => 'net_30',
    'recurring_frequency'  => 'Monthly', // Weekly | Biweekly | FirstFifteenth | Monthly | Yearly
    'recurring_start_date' => '2026-09-01',
    'cover_fee_required'   => true,
    'lines'                => [
        ['item_id' => $item->id, 'name' => 'Retainer', 'quantity' => 1, 'unit_price' => 500],
    ],
]);

$dime->invoices->cancelRecurring('000010', $template->id);
### Subscription plans and subscriptions

A **subscription plan** is a reusable recurring offering; a **subscription** is one
customer's enrollment in a plan. Plans are created as drafts and must be published
before customers can subscribe. Each line references a Merchant `item_id`; the name
and unit price are snapshotted onto the plan (and again onto each subscription).

```php
// Create a draft plan, then publish it
$plan = $dime->subscriptionPlans->create('000010', [
    'name'                => 'Monthly Membership',
    'description'         => 'Full access, billed monthly.',
    'recurrence_schedule' => 'Monthly',   // Weekly, Biweekly, FirstFifteenth, Monthly, Yearly
    'allow_public'        => true,         // list in the public catalog
    'lines'               => [
        ['item_id' => 5, 'name' => 'Base membership', 'quantity' => 1, 'unit_price' => 25.00],
    ],
]);

$plan = $dime->subscriptionPlans->publish('000010', $plan->id);
echo $plan->status;      // "active"
echo $plan->publicUrl;   // hosted subscribe page

// Enroll a customer (charges the first payment against a saved payment method)
$result = $dime->subscriptionPlans->subscribe('000010', $plan->id, [
    'customer_uuid'  => $customer->uuid,
    'payment_method' => $pm->id,
]);
echo $result->subscriptionId;      // 10
echo $result->transactionNumber;   // first charge

// Lifecycle: pause (optionally until a date), resume, cancel
$dime->subscriptions->pause('000010', $result->subscriptionId, '2026-09-01');
$dime->subscriptions->resume('000010', $result->subscriptionId);
$dime->subscriptions->cancel('000010', $result->subscriptionId);

// Plans have their own lifecycle: archive stops new sign-ups but keeps existing
// subscribers; unarchive returns it to draft for re-publishing.
$dime->subscriptionPlans->archive('000010', $plan->id);
$dime->subscriptionPlans->unarchive('000010', $plan->id);

// Read
$plans = $dime->subscriptionPlans->list('000010', ['status' => 'active']);
$subs  = $dime->subscriptions->list('000010', ['status' => 'Active', 'customer_uuid' => $customer->uuid]);
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
