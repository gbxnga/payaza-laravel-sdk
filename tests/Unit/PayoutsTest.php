<?php

declare(strict_types=1);

namespace PayazaSdk\Tests\Unit;

use Orchestra\Testbench\TestCase;
use PayazaSdk\{PayazaServiceProvider, Payaza};
use PayazaSdk\Data\{PayoutBeneficiary, TransactionStatus};
use PayazaSdk\Enums\{Currency, TransactionState};
use Illuminate\Support\Facades\Http;

final class PayoutsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PayazaServiceProvider::class];
    }

    /** @test */
    public function it_sends_payout_successfully(): void
    {
        Http::fake([
            '*' => Http::response([
                'response_content' => ['transaction_status' => 'processing'],
            ], 200),
        ]);

        $beneficiary = new PayoutBeneficiary(
            accountName: 'John Doe',
            accountNumber: '1234567890',
            bankCode: '044',
            amount: 100.0,
            currency: Currency::NGN,
            narration: 'Test payout'
        );

        $status = Payaza::payouts()->send($beneficiary, 'PAYOUT123');

        $this->assertInstanceOf(TransactionStatus::class, $status);
        $this->assertSame(TransactionState::PROCESSING, $status->state);
        $this->assertSame('PAYOUT123', $status->transactionId);
    }

    /** @test */
    public function it_fetches_payout_status(): void
    {
        Http::fake([
            '*' => Http::response([
                'response_content' => ['transaction_status' => 'completed'],
            ], 200),
        ]);

        $status = Payaza::payouts()->status('PAYOUT123');

        $this->assertInstanceOf(TransactionStatus::class, $status);
        $this->assertSame(TransactionState::SUCCESSFUL, $status->state);
    }

    /** @test */
    public function it_fetches_banks_list(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['code' => '044', 'name' => 'Access Bank'],
                    ['code' => '011', 'name' => 'First Bank'],
                ],
            ], 200),
        ]);

        $banks = Payaza::payouts()->getBanks('NG');

        $this->assertIsArray($banks);
        $this->assertCount(2, $banks);
        $this->assertSame('044', $banks[0]['code']);
    }
}