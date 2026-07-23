<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Subscription;
use DimePayments\Sdk\DataObjects\SubscriptionPaymentMethod;

/**
 * The subscription shape returned by show/pause/resume/cancel (includes items).
 * The list endpoint returns the same shape without `items`.
 *
 * @return array<string, mixed>
 */
function fullSubscription(): array
{
    return [
        'id' => 1,
        'subscription_plan_id' => 2,
        'plan_name' => 'Monthly Membership',
        'amount' => '10.00',
        'recurrence_schedule' => 'Monthly',
        'start_date' => '2026-07-22T04:00:00.000000Z',
        'end_date' => null,
        'last_run_date' => '2026-07-22T04:00:00.000000Z',
        'last_run_status' => 'Success',
        'last_run_failed_count' => 0,
        'next_run_date' => null,
        'status' => 'Cancelled',
        'paused_until_date' => null,
        'cancelled_at' => '2026-07-22T20:31:28.000000Z',
        'cancelled_by' => 'Marcus Whitesides APIClient',
        'customer_uuid' => 'e18537dc-bd45-41d2-be2e-fe6e703c4bfa',
        'error' => null,
        'payment_method' => [
            'id' => 5600,
            'type' => 'cc',
            'last_four' => '1111',
            'expiration' => '12/2030',
        ],
        'items' => [
            ['name' => 'General', 'description' => null, 'quantity' => 1, 'unit_price' => 10, 'amount' => 10],
        ],
    ];
}

it('lists subscriptions and paginates across cursors', function () {
    $listItem = fullSubscription();
    unset($listItem['items']);

    [$client] = fakeClient([
        jsonResponse([
            'data' => [$listItem],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['id' => 9] + $listItem],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->subscriptions->list('000010', ['status' => 'Active']);

    expect($page->data[0])->toBeInstanceOf(Subscription::class)
        ->and($page->data[0]->planName)->toBe('Monthly Membership')
        ->and($page->data[0]->amount)->toBe('10.00')
        ->and($page->data[0]->lastRunFailedCount)->toBe(0)
        ->and($page->data[0]->paymentMethod)->toBeInstanceOf(SubscriptionPaymentMethod::class)
        ->and($page->data[0]->paymentMethod->lastFour)->toBe('1111')
        ->and($page->data[0]->items)->toBe([])
        ->and($page->hasMore())->toBeTrue();

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[1]->id)->toBe(9);
});

it('sends the data/filters envelope when listing subscriptions', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => [], 'meta' => []])]);

    $client->subscriptions->list('000010', [
        'status' => 'Active',
        'customer_uuid' => 'cust-uuid',
    ]);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['status' => 'Active', 'customer_uuid' => 'cust-uuid'],
    ]);
});

it('shows a subscription with its payment method and items', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscription()])]);

    $subscription = $client->subscriptions->show('000010', 42);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->subscriptionPlanId)->toBe(2)
        ->and($subscription->status)->toBe('Cancelled')
        ->and($subscription->cancelledBy)->toBe('Marcus Whitesides APIClient')
        ->and($subscription->paymentMethod->id)->toBe(5600)
        ->and($subscription->items)->toHaveCount(1)
        ->and($subscription->items[0]->amount)->toBe('10');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'subscription_id' => 42],
    ]);
});

it('parses a show response returned WITHOUT a data wrapper', function () {
    // The live subscription endpoints return the resource at the top level,
    // not nested under `data`. The SDK must handle both shapes.
    [$client] = fakeClient([jsonResponse(fullSubscription())]);

    $subscription = $client->subscriptions->show('000010', 6);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id)->toBe(1)
        ->and($subscription->planName)->toBe('Monthly Membership')
        ->and($subscription->amount)->toBe('10.00')
        ->and($subscription->paymentMethod->lastFour)->toBe('1111')
        ->and($subscription->items)->toHaveCount(1);
});

it('pauses a subscription with an optional resume date', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscription()])]);

    $client->subscriptions->pause('000010', 42, '2026-09-01');

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/subscription/pause');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'subscription_id' => 42, 'pause_until_date' => '2026-09-01'],
    ]);
});

it('pauses indefinitely when no date is given (null is pruned)', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscription()])]);

    $client->subscriptions->pause('000010', 42);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'subscription_id' => 42],
    ]);
});

it('resumes a subscription with a PATCH', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscription()])]);

    $subscription = $client->subscriptions->resume('000010', 42);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/subscription/resume');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'subscription_id' => 42],
    ]);
});

it('cancels a subscription with a PATCH', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscription()])]);

    $client->subscriptions->cancel('000010', 42);

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/subscription/cancel');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'subscription_id' => 42],
    ]);
});
