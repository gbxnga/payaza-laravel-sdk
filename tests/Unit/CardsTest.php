<?php

declare(strict_types=1);

namespace PayazaSdk\Tests\Unit;

use Orchestra\Testbench\TestCase;
use PayazaSdk\{PayazaServiceProvider, Payaza};
use PayazaSdk\Data\{Card, TransactionStatus};
use PayazaSdk\Enums\{Currency, TransactionState};
use Illuminate\Support\Facades\Http;

final class CardsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PayazaServiceProvider::class];
    }

    /** @test */
    public function it_charges_a_card_successfully(): void
    {
        $this->fakeSuccessfulChargeResponse();

        $status = Payaza::cards()->charge(
            amount:           100,
            card:             new Card('4242424242424242', 12, 2027, '123'),
            transactionRef:   'TEST123',
            currency:         Currency::USD
        );

        $this->assertInstanceOf(TransactionStatus::class, $status);
        $this->assertSame(TransactionState::PENDING, $status->state);
        $this->assertSame('TEST123', $status->transactionId);
    }

    /** @test */
    public function it_fetches_transaction_status(): void
    {
        Http::fake([
            '*' => Http::response([
                'response_content' => ['transaction_status' => 'successful'],
            ], 200),
        ]);

        $status = Payaza::cards()->status('TEST123');

        $this->assertInstanceOf(TransactionStatus::class, $status);
        $this->assertSame(TransactionState::SUCCESSFUL, $status->state);
    }

    /** @test */
    public function it_processes_refunds(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'success'], 200),
        ]);

        $result = Payaza::cards()->refund('TEST123', 50.0);

        $this->assertTrue($result);
    }

    private function fakeSuccessfulChargeResponse(): void
    {
        Http::fake([
            '*' => Http::response([
                'do3dsAuth'  => true,
                'transaction'=> ['transaction_status' => 'pending'],
            ], 200),
        ]);
    }
}