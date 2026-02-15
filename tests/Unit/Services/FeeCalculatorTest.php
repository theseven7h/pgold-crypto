<?php

namespace Tests\Unit\Services;

use App\Services\FeeCalculator;
use Tests\TestCase;

class FeeCalculatorTest extends TestCase
{
    protected FeeCalculator $feeCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feeCalculator = new FeeCalculator();
    }

    public function test_calculates_buy_fee_correctly()
    {
        // 1.5% of 100,000 kobo = 1,500 kobo
        $fee = $this->feeCalculator->calculateBuyFee(100000);
        $this->assertEquals(1500, $fee);

        // 1.5% of 5,000,000 kobo = 75,000 kobo
        $fee = $this->feeCalculator->calculateBuyFee(5000000);
        $this->assertEquals(75000, $fee);
    }

    public function test_calculates_sell_fee_correctly()
    {
        // 1% of 100,000 kobo = 1,000 kobo
        $fee = $this->feeCalculator->calculateSellFee(100000);
        $this->assertEquals(1000, $fee);

        // 1% of 5,000,000 kobo = 50,000 kobo
        $fee = $this->feeCalculator->calculateSellFee(5000000);
        $this->assertEquals(50000, $fee);
    }

    public function test_calculates_buy_total_correctly()
    {
        $result = $this->feeCalculator->calculateBuyTotal(100000);

        $this->assertEquals(100000, $result['amount']);
        $this->assertEquals(1500, $result['fee']);
        $this->assertEquals(101500, $result['total']);
        $this->assertEquals(1.5, $result['fee_percentage']);
    }

    public function test_calculates_sell_net_correctly()
    {
        $result = $this->feeCalculator->calculateSellNet(100000);

        $this->assertEquals(100000, $result['amount']);
        $this->assertEquals(1000, $result['fee']);
        $this->assertEquals(99000, $result['net']);
        $this->assertEquals(1.0, $result['fee_percentage']);
    }

    public function test_fee_rounding_uses_half_up_strategy()
    {
        // 1.5% of 333 = 4.995 -> should round to 5
        $fee = $this->feeCalculator->calculateBuyFee(333);
        $this->assertEquals(5, $fee);

        // 1.5% of 331 = 4.965 -> should round to 5
        $fee = $this->feeCalculator->calculateBuyFee(331);
        $this->assertEquals(5, $fee);
    }
}
