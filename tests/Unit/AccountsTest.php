<?php

declare(strict_types=1);

namespace PayazaSdk\Tests\Unit;

use Orchestra\Testbench\TestCase;
use PayazaSdk\{PayazaServiceProvider, Payaza};
use Illuminate\Support\Facades\Http;

final class AccountsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PayazaServiceProvider::class];
    }

    /** @test */
    public function it_fetches_account_balance(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'available_balance' => 1500.00,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $balance = Payaza::accounts()->balance();

        $this->assertIsArray($balance);
        $this->assertSame(1500.00, $balance['available_balance']);
        $this->assertSame('NGN', $balance['currency']);
    }

    /** @test */
    public function it_fetches_transactions_list(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'transactions' => [
                        ['id' => '1', 'amount' => 100.00],
                        ['id' => '2', 'amount' => 200.00],
                    ],
                ],
            ], 200),
        ]);

        $transactions = Payaza::accounts()->transactions(1, 10);

        $this->assertIsArray($transactions);
        $this->assertArrayHasKey('transactions', $transactions);
        $this->assertCount(2, $transactions['transactions']);
    }

    /** @test */
    public function it_fetches_single_transaction(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'id' => 'TXN123',
                    'amount' => 500.00,
                    'status' => 'successful',
                ],
            ], 200),
        ]);

        $transaction = Payaza::accounts()->transaction('TXN123');

        $this->assertIsArray($transaction);
        $this->assertSame('TXN123', $transaction['id']);
        $this->assertSame(500.00, $transaction['amount']);
    }
}