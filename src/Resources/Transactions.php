<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\CaptureResult;
use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\TokenizeResult;
use DimePayments\Sdk\DataObjects\Transaction;
use DimePayments\Sdk\Exceptions\ApiException;
use DimePayments\Sdk\Exceptions\CaptureException;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Transaction endpoints: charging, authorizing and capturing, tokenizing,
 * refunding, voiding, and reading transactions for a merchant.
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$attributes` and merged into the request's `data` envelope. See
 * each method's array shape for the accepted keys.
 *
 * The methods that move money (charge, authorize, capture, refund, void) take
 * an optional `$idempotencyKey`. Pass your own to make a retry of yours safe:
 * the same key and request get the first response back instead of a second
 * charge. Without one, a random key is used for the call, which keeps the
 * SDK's own automatic retries safe.
 */
final class Transactions extends AbstractResource
{
    /**
     * List transactions for a merchant.
     *
     * @param  array{start_date?: string, end_date?: string, sweep_id?: string, customer_uuid?: string}  $filters
     * @return CursorPage<Transaction>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'transactions',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): Transaction => Transaction::fromArray($item),
        );
    }

    /**
     * Show a single transaction, identified either by `transaction_info_id` or
     * by `transaction_id` + `transaction_type` (CC|ACH).
     *
     * @param  array{transaction_info_id?: int|string, transaction_id?: int|string, transaction_type?: string}  $identifier
     */
    public function show(string $sid, array $identifier): Transaction
    {
        $raw = $this->transport->request('GET', 'transaction', $this->envelope(['sid' => $sid] + $identifier));

        return Transaction::fromArray($raw['data'] ?? []);
    }

    /**
     * Charge a card, either by `token` or by raw card details. Raw card details
     * require the merchant to be PCI compliant.
     *
     * @param  array{
     *     amount: int|float|string,
     *     token?: string,
     *     cardholder_name?: string,
     *     card_number?: int|string,
     *     expiration_date?: string,
     *     cvv?: int|string,
     *     phone?: string,
     *     email?: string,
     *     customer_uuid?: string,
     *     memo?: string,
     *     billing_address?: array<string, mixed>,
     *     shipping_address?: array<string, mixed>,
     *     shipping_address_enabled?: bool
     * }  $attributes
     */
    public function chargeCard(string $sid, array $attributes, ?string $idempotencyKey = null): Transaction
    {
        $raw = $this->transport->request('POST', 'transaction/charge-card', $this->envelope(['sid' => $sid] + $attributes), headers: self::idempotencyHeaders($idempotencyKey));

        return Transaction::fromArray($raw['data'] ?? []);
    }

    /**
     * Charge a previously tokenized card.
     *
     * @deprecated The API marks this endpoint deprecated; prefer {@see chargeCard()} with a `token`.
     *
     * @param  array{
     *     token: string,
     *     amount: int|float|string,
     *     first_name: string,
     *     last_name: string,
     *     phone: string,
     *     email?: string,
     *     memo?: string,
     *     billing_address?: array<string, mixed>
     * }  $attributes
     */
    public function chargeCardToken(string $sid, array $attributes, ?string $idempotencyKey = null): Transaction
    {
        $raw = $this->transport->request('POST', 'transaction/charge-card-token', $this->envelope(['sid' => $sid] + $attributes), headers: self::idempotencyHeaders($idempotencyKey));

        return Transaction::fromArray($raw['data'] ?? []);
    }

    /**
     * Charge a bank account via ACH. Created in a pending state.
     *
     * @param  array{
     *     routing_number: int|string,
     *     account_number: int|string,
     *     account_type: string,
     *     account_name: string,
     *     amount: int|float|string,
     *     phone?: string,
     *     email?: string,
     *     customer_uuid?: string,
     *     memo?: string,
     *     billing_address?: array<string, mixed>,
     *     shipping_address?: array<string, mixed>
     * }  $attributes
     */
    public function chargeAch(string $sid, array $attributes, ?string $idempotencyKey = null): Transaction
    {
        $raw = $this->transport->request('POST', 'transaction/charge-ach', $this->envelope(['sid' => $sid] + $attributes), headers: self::idempotencyHeaders($idempotencyKey));

        return Transaction::fromArray($raw['data'] ?? []);
    }

    /**
     * Authorize a card: place a hold for `amount` without moving any money.
     * Supply the card either by `token` or by raw card details
     * (`cardholder_name`, `card_number`, `expiration_date` and
     * `billing_address.zip`); raw card details require the merchant to be PCI
     * compliant.
     *
     * The returned `transactionNumber` is the handle on the hold: pass it to
     * {@see capture()} to collect, or to {@see void()} with type "CC" to release
     * it. Capture promptly — typically within 24 hours — since the issuer drops
     * an uncaptured hold on its own schedule. Voiding needs its own API key
     * ability.
     *
     * @param  array{
     *     amount: int|float|string,
     *     token?: string,
     *     cardholder_name?: string,
     *     card_number?: int|string,
     *     expiration_date?: string,
     *     cvv?: int|string,
     *     phone?: string,
     *     email?: string,
     *     customer_uuid?: string,
     *     memo?: string,
     *     billing_address?: array<string, mixed>,
     *     shipping_address?: array<string, mixed>
     * }  $attributes
     */
    public function authorize(string $sid, array $attributes, ?string $idempotencyKey = null): Transaction
    {
        $raw = $this->transport->request('POST', 'transaction/authorize', $this->envelope(['sid' => $sid] + $attributes), headers: self::idempotencyHeaders($idempotencyKey));

        return Transaction::fromArray($raw['data'] ?? []);
    }

    /**
     * Capture an authorization made with {@see authorize()}, moving the money.
     * `$transactionId` is the authorization's `transactionNumber`. Omit
     * `$amount` to capture the full authorized amount, or pass less to capture
     * part of it.
     *
     * An authorization can be captured only once: a partial capture settles that
     * amount and releases the rest of the hold. To collect in instalments,
     * authorize each one separately. Once captured it is an ordinary card
     * payment that can be refunded or voided.
     *
     * Returns what was captured. A refused capture throws {@see CaptureException},
     * whose `errorCode` says why (`already_captured`, `voided`,
     * `processor_declined`, ...).
     *
     * @throws CaptureException
     */
    public function capture(string $sid, int|string $transactionId, int|float|string|null $amount = null, ?string $idempotencyKey = null): CaptureResult
    {
        try {
            $raw = $this->transport->request('POST', 'transaction/capture', $this->envelope([
                'sid' => $sid,
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]), headers: self::idempotencyHeaders($idempotencyKey));
        } catch (ApiException $e) {
            throw self::captureException($e) ?? $e;
        }

        return CaptureResult::fromArray($raw['data'] ?? $raw);
    }

    /**
     * A refused capture carries `data.code` beside its message; anything without
     * one (an older API, an idempotency refusal) stays the exception it was.
     */
    private static function captureException(ApiException $e): ?CaptureException
    {
        $data = $e->getResponseBody()['data'] ?? null;

        if ($e->getStatusCode() !== 400 || ! is_array($data) || ! isset($data['code']) || ! is_string($data['code'])) {
            return null;
        }

        return new CaptureException(
            message: $e->getMessage(),
            errorCode: $data['code'],
            reason: isset($data['reason']) && is_string($data['reason']) ? $data['reason'] : null,
            statusCode: $e->getStatusCode(),
            responseBody: $e->getResponseBody(),
            previous: $e,
        );
    }

    /**
     * Tokenize a card without charging it. Requires PCI compliance.
     *
     * @param  array{
     *     cardholder_name: string,
     *     card_number: int|string,
     *     expiration_date: string,
     *     cvv?: int|string,
     *     billing_address?: array<string, mixed>
     * }  $attributes
     */
    public function tokenizeCard(string $sid, array $attributes): TokenizeResult
    {
        $raw = $this->transport->request('POST', 'transaction/tokenize-card', $this->envelope(['sid' => $sid] + $attributes));

        return TokenizeResult::fromArray($raw['data'] ?? []);
    }

    /**
     * Refund (or, for some CC cases, void) a transaction. Identify it either by
     * `transaction_info_id` or by `transaction_id` + `transaction_type`.
     *
     * @param  array{
     *     amount: int|float|string,
     *     transaction_info_id?: int|string,
     *     transaction_id?: int|string,
     *     transaction_type?: string
     * }  $attributes
     */
    public function refund(string $sid, array $attributes, ?string $idempotencyKey = null): MessageResult
    {
        $raw = $this->transport->request('POST', 'transaction/refund', $this->envelope(['sid' => $sid] + $attributes), headers: self::idempotencyHeaders($idempotencyKey));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }

    /**
     * Void a transaction (immediate reversal, no waiting period).
     */
    public function void(string $sid, string $transactionType, int|string $transactionId, ?string $idempotencyKey = null): MessageResult
    {
        $raw = $this->transport->request('PATCH', 'transaction/void', $this->envelope([
            'sid' => $sid,
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
        ]), headers: self::idempotencyHeaders($idempotencyKey));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }
}
