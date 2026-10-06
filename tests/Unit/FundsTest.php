<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\FundRelease;
use DimePayments\Sdk\DataObjects\FundReleaseResult;
use DimePayments\Sdk\DataObjects\FundsBalance;
use DimePayments\Sdk\DataObjects\ReleasableTransaction;
use DimePayments\Sdk\DataObjects\ReleasableTransactions;
use DimePayments\Sdk\Exceptions\ApiException;

/**
 * A release record as returned under `data.release`.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function fundRelease(array $overrides = []): array
{
    return $overrides + [
        'id' => 42,
        'amount' => 1500,
        'fee' => 30,
        'status' => 'released',
        'status_label' => 'Released',
        'idempotency_key' => 'payout-2026-09-25-0001',
        'transaction_info_ids' => null,
        'failure_reason' => null,
        'requested_at' => '2026-09-25T14:02:11+00:00',
        'completed_at' => '2026-09-25T14:02:12+00:00',
    ];
}

it('reads the held balance', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '91828382',
            'available' => 6492.87,
            'pending' => 0,
            'reserve' => 0,
            'at_risk' => 1250,
            'owed_to_split' => 40.15,
            'unresolved' => 0,
            'release_fee' => 30,
            'releasable' => 5172.72,
            'ach_settlement_days' => 7,
            'ach_out_enabled' => true,
            'ach_out_limit_remaining' => 19999999.99,
        ]]),
    ]);

    $balance = $client->funds->balance('91828382');

    expect($balance)->toBeInstanceOf(FundsBalance::class)
        ->and($balance->available)->toBe('6492.87')
        ->and($balance->atRisk)->toBe('1250')
        ->and($balance->owedToSplit)->toBe('40.15')
        ->and($balance->releaseFee)->toBe('30')
        ->and($balance->releasable)->toBe('5172.72')
        ->and($balance->achSettlementDays)->toBe(7)
        ->and($balance->achOutEnabled)->toBeTrue()
        ->and($balance->achOutLimitRemaining)->toBe('19999999.99');

    expect($history[0]['request']->getMethod())->toBe('GET')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/funds/balance');
    expect(sentJson($history))->toBe(['data' => ['sid' => '91828382']]);
});

it('raises ApiException for a merchant whose funds are not held', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => "This merchant's funds are not held; they settle automatically."]], 422),
    ]);

    $client->funds->balance('000010');
})->throws(ApiException::class, "This merchant's funds are not held; they settle automatically.");

it('lists releasable transactions', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '91828382',
            'transactions' => [
                [
                    'transaction_info_id' => '1297431',
                    'type' => 'CC',
                    'transaction_date' => '2026-09-28T15:12:09+00:00',
                    'gross_amount' => 100,
                    'net_amount' => 97,
                    'split_amount' => 1.25,
                    'amount' => 95.75,
                ],
            ],
            'total' => 95.75,
            'truncated' => false,
        ]]),
    ]);

    $releasable = $client->funds->transactions('91828382');

    expect($releasable)->toBeInstanceOf(ReleasableTransactions::class)
        ->and($releasable->total)->toBe('95.75')
        ->and($releasable->truncated)->toBeFalse()
        ->and($releasable->transactions)->toHaveCount(1)
        ->and($releasable->transactions[0])->toBeInstanceOf(ReleasableTransaction::class)
        ->and($releasable->transactions[0]->transactionInfoId)->toBe('1297431')
        ->and($releasable->transactions[0]->type)->toBe('CC')
        ->and($releasable->transactions[0]->netAmount)->toBe('97')
        ->and($releasable->transactions[0]->splitAmount)->toBe('1.25')
        ->and($releasable->transactions[0]->amount)->toBe('95.75');

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/funds/transactions');
    expect(sentJson($history))->toBe(['data' => ['sid' => '91828382']]);
});

it('releases funds by amount', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '91828382',
            'replayed' => false,
            'release' => fundRelease(),
        ]], 201),
    ]);

    $result = $client->funds->release('91828382', [
        'amount' => 1500.50,
        'idempotency_key' => 'payout-2026-09-25-0001',
    ]);

    expect($result)->toBeInstanceOf(FundReleaseResult::class)
        ->and($result->replayed)->toBeFalse()
        ->and($result->release)->toBeInstanceOf(FundRelease::class)
        ->and($result->release->id)->toBe(42)
        ->and($result->release->amount)->toBe('1500')
        ->and($result->release->fee)->toBe('30')
        ->and($result->release->status)->toBe('released')
        ->and($result->release->idempotencyKey)->toBe('payout-2026-09-25-0001')
        ->and($result->release->transactionInfoIds)->toBe([]);

    expect($history[0]['request']->getMethod())->toBe('POST')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/funds/release');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '91828382', 'amount' => 1500.50, 'idempotency_key' => 'payout-2026-09-25-0001'],
    ]);
});

it('releases funds by transaction, sending the ids as strings', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '91828382',
            'replayed' => true,
            'release' => fundRelease([
                'status' => 'unknown',
                'status_label' => 'Unknown',
                'transaction_info_ids' => ['1297431', '1297432'],
                'completed_at' => null,
            ]),
        ]]),
    ]);

    $result = $client->funds->release('91828382', [
        'transaction_info_ids' => [1297431, '1297432'],
        'idempotency_key' => 'payout-2026-09-25-0002',
    ]);

    expect($result->replayed)->toBeTrue()
        ->and($result->release->status)->toBe('unknown')
        ->and($result->release->transactionInfoIds)->toBe(['1297431', '1297432'])
        ->and($result->release->completedAt)->toBeNull();

    expect(sentJson($history))->toBe([
        'data' => [
            'sid' => '91828382',
            'transaction_info_ids' => ['1297431', '1297432'],
            'idempotency_key' => 'payout-2026-09-25-0002',
        ],
    ]);
});

it('returns a declined release rather than throwing', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '91828382',
            'replayed' => false,
            'release' => fundRelease([
                'status' => 'failed',
                'status_label' => 'Failed',
                'failure_reason' => 'Insufficient funds',
                'completed_at' => null,
            ]),
        ]], 422),
    ]);

    $result = $client->funds->release('91828382', [
        'amount' => 1500,
        'idempotency_key' => 'payout-2026-09-25-0003',
    ]);

    expect($result->release->status)->toBe('failed')
        ->and($result->release->failureReason)->toBe('Insufficient funds');
});

it('throws when a release is refused without a record', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'message' => 'Some of those payments cannot be released.',
            'ineligible' => [['transaction_info_id' => '1297431', 'reason' => 'Already released.']],
        ]], 422),
    ]);

    try {
        $client->funds->release('91828382', [
            'transaction_info_ids' => ['1297431'],
            'idempotency_key' => 'payout-2026-09-25-0004',
        ]);

        $this->fail('Expected an ApiException.');
    } catch (ApiException $e) {
        expect($e->getStatusCode())->toBe(422)
            ->and($e->getMessage())->toBe('Some of those payments cannot be released.')
            ->and($e->getResponseBody()['data']['ineligible'][0]['transaction_info_id'])->toBe('1297431');
    }
});

it('throws on an idempotency key conflict', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'That idempotency key was used for a different release.']], 409),
    ]);

    $client->funds->release('91828382', ['amount' => 10, 'idempotency_key' => 'reused']);
})->throws(ApiException::class, 'That idempotency key was used for a different release.');
