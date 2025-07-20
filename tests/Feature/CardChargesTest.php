<?php

declare(strict_types=1);

namespace PayazaSdk\Tests\Feature;

use Orchestra\Testbench\TestCase;
use PayazaSdk\{PayazaServiceProvider, Payaza};
use PayazaSdk\Data\Card;
use PayazaSdk\Enums\Currency;

final class CardChargesTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PayazaServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('PAYAZA_INTEGRATION')) {
            $this->markTestSkipped('Set PAYAZA_INTEGRATION=1 to run live tests');
        }
    }

    /** @test */
    public function it_can_charge_and_poll_live(): void
    {
        $ref = 'IT-' . uniqid();
        $status = Payaza::cards()->charge(
            1.00,
            new Card('4242424242424242', 12, 2028, '123'),
            $ref,
            Currency::USD
        );

        sleep(3);
        $polled = Payaza::cards()->status($ref);

        $this->assertSame($ref, $polled->transactionId);
    }

    /** @test */
    public function it_can_process_payout_and_check_status(): void
    {
        $ref = 'PAYOUT-' . uniqid();
        
        $beneficiary = new \PayazaSdk\Data\PayoutBeneficiary(
            accountName: 'Test User',
            accountNumber: '0123456789',
            bankCode: '044',
            amount: 100.0,
            currency: Currency::NGN
        );

        $status = Payaza::payouts()->send($beneficiary, $ref);

        sleep(2);
        $polled = Payaza::payouts()->status($ref);

        $this->assertSame($ref, $polled->transactionId);
    }

    /** @test */
    public function it_can_fetch_account_data(): void
    {
        $balance = Payaza::accounts()->balance();
        $this->assertIsArray($balance);

        $transactions = Payaza::accounts()->transactions(1, 5);
        $this->assertIsArray($transactions);
    }
}