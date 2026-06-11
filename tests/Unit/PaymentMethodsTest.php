<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\PaymentMethod;

it('creates a card payment method and returns a typed object', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'id' => 77,
            'type' => 'cc',
            'token' => 'tok_card_abc',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'cc_name_on_card' => 'John Doe',
            'cc_last_four' => '4242',
            'cc_expiration_date' => '12/27',
            'cc_brand' => 'Visa',
            'status' => 'active',
            'enabled' => true,
            'default' => true,
            'addr1' => '12 Street Ave',
            'city' => 'Boulder',
            'state' => 'CO',
            'zip' => '80302',
        ]]),
    ]);

    $paymentMethod = $client->paymentMethods->create('000010', [
        'uuid' => 'abc-123',
        'type' => 'cc',
        'cc_name_on_card' => 'John Doe',
        'cc_number' => '4242424242424242',
        'cc_expiration_date' => '12/27',
        'cc_cvv' => '123',
        'cc_brand' => 'Visa',
        'addr1' => '12 Street Ave',
        'city' => 'Boulder',
        'state' => 'CO',
        'zip' => '80302',
        'default' => true,
    ]);

    expect($paymentMethod)->toBeInstanceOf(PaymentMethod::class)
        ->and($paymentMethod->type)->toBe('cc')
        ->and($paymentMethod->ccLastFour)->toBe('4242')
        ->and($paymentMethod->ccBrand)->toBe('Visa')
        ->and($paymentMethod->isDefault)->toBeTrue()
        ->and($paymentMethod->enabled)->toBeTrue();

    // Request was wrapped in the data envelope with the sid merged in.
    $sent = sentJson($history);
    expect($sent)->toBe([
        'data' => [
            'sid' => '000010',
            'uuid' => 'abc-123',
            'type' => 'cc',
            'cc_name_on_card' => 'John Doe',
            'cc_number' => '4242424242424242',
            'cc_expiration_date' => '12/27',
            'cc_cvv' => '123',
            'cc_brand' => 'Visa',
            'addr1' => '12 Street Ave',
            'city' => 'Boulder',
            'state' => 'CO',
            'zip' => '80302',
            'default' => true,
        ],
    ]);
});

it('paginates payment methods and yields typed objects', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [[
                'id' => 1,
                'type' => 'cc',
                'cc_last_four' => '4242',
            ]],
            'links' => ['next' => 'http://x/api/payment-method/list?cursor=page2'],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [[
                'id' => 2,
                'type' => 'ach',
                'ach_bank_account_name' => 'John Doe',
                'ach_routing_number' => '011000015',
                'ach_account_number' => '000123456789',
                'ach_ownership_type' => 'personal',
                'ach_account_type' => 'checking',
                'ach_bank_name' => 'Test Bank',
            ]],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->paymentMethods->list('000010', ['uuid' => 'abc-123']);

    expect($page->count())->toBe(1)
        ->and($page->hasMore())->toBeTrue()
        ->and($page->perPage)->toBe(500);

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[0])->toBeInstanceOf(PaymentMethod::class)
        ->and($all[0]->type)->toBe('cc')
        ->and($all[0]->ccLastFour)->toBe('4242')
        ->and($all[1]->type)->toBe('ach')
        ->and($all[1]->achAccountType)->toBe('checking')
        ->and($all[1]->achBankName)->toBe('Test Bank');
});

it('shows a payment method, sending the customer under filters', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'id' => 77,
            'type' => 'cc',
            'cc_last_four' => '4242',
        ]]),
    ]);

    $paymentMethod = $client->paymentMethods->show('000010', 77, ['email' => 'john@example.com']);

    expect($paymentMethod->id)->toBe(77)
        ->and($paymentMethod->ccLastFour)->toBe('4242');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'payment_method_id' => 77],
        'filters' => ['email' => 'john@example.com'],
    ]);
});
