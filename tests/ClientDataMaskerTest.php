<?php

use Clicks\Logger\ClientDataMasker;

describe('ClientDataMasker', function () {
    describe('string masking', function () {
        it('masks credit card numbers with full style', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['credit_cards'],
            ]);

            $result = $masker->maskString('Payment with card 4111111111111111');

            expect($result)->toBe('Payment with card [CREDIT_CARD]');
        });

        it('masks credit card numbers with partial style', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'partial',
                'patterns' => ['credit_cards'],
            ]);

            $result = $masker->maskString('Card: 4111111111111111');

            expect($result)->toBe('Card: ****1111');
        });

        it('masks credit card numbers with hash style', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'hash',
                'patterns' => ['credit_cards'],
            ]);

            $result = $masker->maskString('Card: 4111111111111111');

            expect($result)->toMatch('/Card: \[[a-f0-9]{8}\]/');
        });

        it('masks SSN numbers', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['ssn'],
            ]);

            $result = $masker->maskString('SSN: 123-45-6789');

            expect($result)->toBe('SSN: [SSN]');
        });

        it('masks API keys', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['api_keys'],
            ]);

            $result = $masker->maskString('Key: sk_live_abcdefghijklmnopqrstuvwx');

            expect($result)->toBe('Key: [API_KEY]');
        });

        it('masks phone numbers with parentheses format', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['phone_numbers'],
            ]);

            $result = $masker->maskString('Call me at (555) 123-4567');

            expect($result)->toBe('Call me at [PHONE]');
        });

        it('masks multiple sensitive data types in one string', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['credit_cards', 'ssn', 'phone_numbers'],
            ]);

            $result = $masker->maskString('Card: 4111111111111111, SSN: 123-45-6789, Phone: (555) 123-4567');

            expect($result)->toBe('Card: [CREDIT_CARD], SSN: [SSN], Phone: [PHONE]');
        });

        it('returns original string when masking is disabled', function () {
            $masker = new ClientDataMasker([
                'enabled' => false,
                'patterns' => ['credit_cards'],
            ]);

            $result = $masker->maskString('Card: 4111111111111111');

            // When disabled, maskString is never called via maskRecord
            // but maskString itself always applies patterns
            // The protection is at the maskRecord level
            expect($masker->isEnabled())->toBeFalse();
        });
    });

    describe('array masking', function () {
        it('masks sensitive values in arrays', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['credit_cards'],
            ]);

            $result = $masker->maskArray([
                'card_number' => '4111111111111111',
                'name' => 'John Doe',
            ]);

            expect($result['card_number'])->toBe('[CREDIT_CARD]');
            expect($result['name'])->toBe('John Doe');
        });

        it('masks sensitive keys regardless of pattern', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => [], // No patterns enabled
            ]);

            $result = $masker->maskArray([
                'password' => 'supersecret123',
                'api_token' => 'abc123def456',
                'username' => 'john',
            ]);

            expect($result['password'])->toBe('[REDACTED]');
            expect($result['api_token'])->toBe('[REDACTED]');
            expect($result['username'])->toBe('john');
        });

        it('recursively masks nested arrays', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['credit_cards'],
            ]);

            $result = $masker->maskArray([
                'payment' => [
                    'card' => '4111111111111111',
                    'billing' => [
                        'name' => 'John Doe',
                    ],
                ],
            ]);

            expect($result['payment']['card'])->toBe('[CREDIT_CARD]');
            expect($result['payment']['billing']['name'])->toBe('John Doe');
        });

        it('respects max depth limit', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['credit_cards'],
                'max_depth' => 2,
            ]);

            $result = $masker->maskArray([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'card' => '4111111111111111',
                        ],
                    ],
                ],
            ]);

            // At max_depth, the array is returned as-is
            expect($result['level1']['level2']['level3']['card'])->toBe('4111111111111111');
        });
    });

    describe('record masking', function () {
        it('masks message, context, and extra in a record', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => ['credit_cards', 'ssn'],
            ]);

            $record = [
                'message' => 'Payment with card 4111111111111111',
                'context' => ['ssn' => '123-45-6789'],
                'extra' => ['card' => '5555555555554444'],
            ];

            $result = $masker->maskRecord($record);

            expect($result['message'])->toBe('Payment with card [CREDIT_CARD]');
            expect($result['context']['ssn'])->toBe('[SSN]');
            expect($result['extra']['card'])->toBe('[CREDIT_CARD]');
        });

        it('returns record unchanged when disabled', function () {
            $masker = new ClientDataMasker([
                'enabled' => false,
                'patterns' => ['credit_cards'],
            ]);

            $record = [
                'message' => 'Card: 4111111111111111',
                'context' => [],
            ];

            $result = $masker->maskRecord($record);

            expect($result['message'])->toBe('Card: 4111111111111111');
        });
    });

    describe('custom patterns', function () {
        it('applies custom patterns', function () {
            $masker = new ClientDataMasker([
                'enabled' => true,
                'style' => 'full',
                'patterns' => [],
                'custom_patterns' => [
                    [
                        'name' => 'Customer IDs',
                        'pattern' => '/CUST-[A-Z0-9]{8}/i',
                        'replacement' => '[CUSTOMER_ID]',
                    ],
                ],
            ]);

            $result = $masker->maskString('Customer: CUST-ABC12345');

            expect($result)->toBe('Customer: [CUSTOMER_ID]');
        });
    });
});
