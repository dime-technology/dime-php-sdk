<?php

declare(strict_types=1);

namespace DimePayments\Sdk;

use DimePayments\Sdk\Exceptions\DimeException;
use DimePayments\Sdk\Http\Transport;
use DimePayments\Sdk\Resources\Addresses;
use DimePayments\Sdk\Resources\Customers;
use DimePayments\Sdk\Resources\Deposits;
use DimePayments\Sdk\Resources\Invoices;
use DimePayments\Sdk\Resources\Merchants;
use DimePayments\Sdk\Resources\PaymentMethods;
use DimePayments\Sdk\Resources\RecurringPayments;
use DimePayments\Sdk\Resources\SubscriptionPlans;
use DimePayments\Sdk\Resources\Subscriptions;
use DimePayments\Sdk\Resources\Transactions;

/**
 * Entry point for the Dime Payments API.
 *
 * ```php
 * $dime = new \DimePayments\Sdk\Client('your-api-token');
 * $transaction = $dime->transactions->chargeCard('000010', [
 *     'amount' => 49.99,
 *     'token'  => 'tok_abc123',
 * ]);
 * ```
 *
 * Resources are exposed as readonly properties; each maps to a group of API
 * endpoints. All methods throw a {@see DimeException}
 * subclass on failure.
 */
final class Client
{
    public readonly Transactions $transactions;

    public readonly Customers $customers;

    public readonly PaymentMethods $paymentMethods;

    public readonly Merchants $merchants;

    public readonly Addresses $addresses;

    public readonly Deposits $deposits;

    public readonly RecurringPayments $recurringPayments;

    public readonly Invoices $invoices;

    public readonly SubscriptionPlans $subscriptionPlans;

    public readonly Subscriptions $subscriptions;

    private readonly Config $config;

    /**
     * @param  string|Config  $token  An API token, or a fully-built {@see Config} for advanced setups.
     */
    public function __construct(string|Config $token, string $baseUrl = Config::DEFAULT_BASE_URL)
    {
        $this->config = $token instanceof Config
            ? $token
            : new Config(token: $token, baseUrl: $baseUrl);

        $transport = new Transport($this->config);

        $this->transactions = new Transactions($transport);
        $this->customers = new Customers($transport);
        $this->paymentMethods = new PaymentMethods($transport);
        $this->merchants = new Merchants($transport);
        $this->addresses = new Addresses($transport);
        $this->deposits = new Deposits($transport);
        $this->recurringPayments = new RecurringPayments($transport);
        $this->invoices = new Invoices($transport);
        $this->subscriptionPlans = new SubscriptionPlans($transport);
        $this->subscriptions = new Subscriptions($transport);
    }

    public function config(): Config
    {
        return $this->config;
    }
}
