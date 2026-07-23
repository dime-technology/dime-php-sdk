<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\SubscribeResult;
use DimePayments\Sdk\DataObjects\SubscriptionPlan;

/**
 * The plan shape returned by list/show/create/edit/publish/archive/unarchive.
 *
 * @return array<string, mixed>
 */
function fullSubscriptionPlan(): array
{
    return [
        'id' => 1,
        'name' => 'Monthly Membership',
        'description' => null,
        'recurrence_schedule' => 'Monthly',
        'status' => 'active',
        'subtotal' => 20,
        'total' => 20,
        'token' => 'aBQcMJFZVKtsYNC1CWfxca2tvwB1Ls52yMuzT7JI',
        'public_url' => 'http://relictum.test/subscribe/aBQcMJFZVKtsYNC1CWfxca2tvwB1Ls52yMuzT7JI',
        'allow_public' => true,
        'created_at' => '2026-07-22T13:16:46-04:00',
        'items' => [
            ['name' => 'General', 'description' => null, 'quantity' => 2, 'unit_price' => 10],
        ],
    ];
}

it('lists subscription plans and paginates across cursors', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [fullSubscriptionPlan()],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['id' => 2, 'name' => 'Yearly'] + fullSubscriptionPlan()],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->subscriptionPlans->list('000010', ['status' => 'active']);

    expect($page->data[0])->toBeInstanceOf(SubscriptionPlan::class)
        ->and($page->data[0]->name)->toBe('Monthly Membership')
        ->and($page->data[0]->subtotal)->toBe('20')
        ->and($page->data[0]->allowPublic)->toBeTrue()
        ->and($page->data[0]->items[0]->unitPrice)->toBe('10')
        ->and($page->hasMore())->toBeTrue();

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2);
});

it('sends the data/filters envelope when listing plans', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => [], 'meta' => []])]);

    $client->subscriptionPlans->list('000010', ['status' => 'active']);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['status' => 'active'],
    ]);
});

it('shows a subscription plan with its items', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscriptionPlan()])]);

    $plan = $client->subscriptionPlans->show('000010', 42);

    expect($plan)->toBeInstanceOf(SubscriptionPlan::class)
        ->and($plan->recurrenceSchedule)->toBe('Monthly')
        ->and($plan->status)->toBe('active')
        ->and($plan->total)->toBe('20')
        ->and($plan->items)->toHaveCount(1)
        ->and($plan->items[0]->name)->toBe('General')
        ->and($plan->items[0]->amount)->toBeNull();

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'subscription_plan_id' => 42],
    ]);
});

it('parses a show response returned WITHOUT a data wrapper', function () {
    // The live subscription endpoints return the resource at the top level,
    // unlike the rest of the API (which nests it under `data`). The SDK must
    // handle both shapes.
    [$client] = fakeClient([jsonResponse(fullSubscriptionPlan())]);

    $plan = $client->subscriptionPlans->show('000010', 13);

    expect($plan)->toBeInstanceOf(SubscriptionPlan::class)
        ->and($plan->id)->toBe(1)
        ->and($plan->name)->toBe('Monthly Membership')
        ->and($plan->total)->toBe('20')
        ->and($plan->items)->toHaveCount(1);
});

it('creates a subscription plan and sends the line items', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscriptionPlan()], 201)]);

    $plan = $client->subscriptionPlans->create('000010', [
        'name' => 'Monthly Membership',
        'description' => 'Full access, billed monthly.',
        'recurrence_schedule' => 'Monthly',
        'allow_public' => true,
        'lines' => [
            ['item_id' => 5, 'name' => 'Base membership', 'quantity' => 1, 'unit_price' => 25],
        ],
    ]);

    expect($plan)->toBeInstanceOf(SubscriptionPlan::class)
        ->and($plan->id)->toBe(1);

    $sent = sentJson($history);
    expect($sent['data']['sid'])->toBe('000010')
        ->and($sent['data']['name'])->toBe('Monthly Membership')
        ->and($sent['data']['lines'][0])->toBe([
            'item_id' => 5, 'name' => 'Base membership', 'quantity' => 1, 'unit_price' => 25,
        ]);
});

it('updates a plan with a PATCH and the id merged into data', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => fullSubscriptionPlan()])]);

    $client->subscriptionPlans->update('000010', 42, [
        'name' => 'Monthly Membership',
        'recurrence_schedule' => 'Yearly',
        'lines' => [['item_id' => 5, 'name' => 'Base', 'quantity' => 1, 'unit_price' => 25]],
    ]);

    expect($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/subscription-plan/edit');

    $sent = sentJson($history);
    expect($sent['data']['sid'])->toBe('000010')
        ->and($sent['data']['subscription_plan_id'])->toBe(42)
        ->and($sent['data']['recurrence_schedule'])->toBe('Yearly');
});

it('deletes a plan and returns a message', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'Subscription plan deleted']]),
    ]);

    $result = $client->subscriptionPlans->delete('000010', 42);

    expect($result)->toBeInstanceOf(MessageResult::class)
        ->and($result->message)->toBe('Subscription plan deleted');
    expect($history[0]['request']->getMethod())->toBe('POST');
    expect(sentJson($history))->toBe(['data' => ['sid' => '000010', 'subscription_plan_id' => 42]]);
});

it('publishes, archives, and unarchives a plan with a PATCH', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => fullSubscriptionPlan()]),
        jsonResponse(['data' => fullSubscriptionPlan()]),
        jsonResponse(['data' => fullSubscriptionPlan()]),
    ]);

    $client->subscriptionPlans->publish('000010', 42);
    $client->subscriptionPlans->archive('000010', 42);
    $client->subscriptionPlans->unarchive('000010', 42);

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/subscription-plan/publish')
        ->and($history[0]['request']->getMethod())->toBe('PATCH')
        ->and($history[1]['request']->getUri()->getPath())->toBe('/api/subscription-plan/archive')
        ->and($history[2]['request']->getUri()->getPath())->toBe('/api/subscription-plan/unarchive');
    expect(sentJson($history, 2))->toBe(['data' => ['sid' => '000010', 'subscription_plan_id' => 42]]);
});

it('subscribes a customer to a plan', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'subscription_id' => 10,
            'status' => 'Active',
            'next_run_date' => '2026-08-21',
            'transaction_number' => 'TXN-123',
            'amount' => 25.0,
        ]], 201),
    ]);

    $result = $client->subscriptionPlans->subscribe('000010', 42, [
        'customer_uuid' => '9b1c-uuid',
        'payment_method' => 88,
    ]);

    expect($result)->toBeInstanceOf(SubscribeResult::class)
        ->and($result->subscriptionId)->toBe(10)
        ->and($result->status)->toBe('Active')
        ->and($result->transactionNumber)->toBe('TXN-123')
        ->and($result->amount)->toBe('25');

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/subscription-plan/subscribe');
    expect(sentJson($history))->toBe([
        'data' => [
            'sid' => '000010',
            'subscription_plan_id' => 42,
            'customer_uuid' => '9b1c-uuid',
            'payment_method' => 88,
        ],
    ]);
});
