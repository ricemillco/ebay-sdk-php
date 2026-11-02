<?php
namespace DTS\eBaySDK\Constants\Test;

use DTS\eBaySDK\Constants\MarketplaceIds as MarketplaceIds;

class MarketplaceIdsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantsAreCorrectType()
    {
        $this->assertIsString(MarketplaceIds::US);
        $this->assertIsString(MarketplaceIds::CA);
        $this->assertIsString(MarketplaceIds::GB);
        $this->assertIsString(MarketplaceIds::AU);
        $this->assertIsString(MarketplaceIds::AT);
        $this->assertIsString(MarketplaceIds::BE);
        $this->assertIsString(MarketplaceIds::FR);
        $this->assertIsString(MarketplaceIds::DE);
        $this->assertIsString(MarketplaceIds::MOTORS);
        $this->assertIsString(MarketplaceIds::IT);
        $this->assertIsString(MarketplaceIds::NL);
        $this->assertIsString(MarketplaceIds::ES);
        $this->assertIsString(MarketplaceIds::CH);
        $this->assertIsString(MarketplaceIds::HK);
        $this->assertIsString(MarketplaceIds::IN);
        $this->assertIsString(MarketplaceIds::IE);
        $this->assertIsString(MarketplaceIds::MY);
        $this->assertIsString(MarketplaceIds::PH);
        $this->assertIsString(MarketplaceIds::PL);
        $this->assertIsString(MarketplaceIds::SG);
        $this->assertIsString(MarketplaceIds::CN);
    }

    public function testConstantsHaveCorrectValue()
    {
        $this->assertEquals('EBAY-US', MarketplaceIds::US);
        $this->assertEquals('EBAY-CA', MarketplaceIds::CA);
        $this->assertEquals('EBAY-GB', MarketplaceIds::GB);
        $this->assertEquals('EBAY-AU', MarketplaceIds::AU);
        $this->assertEquals('EBAY-AT', MarketplaceIds::AT);
        $this->assertEquals('EBAY-BE', MarketplaceIds::BE);
        $this->assertEquals('EBAY-FR', MarketplaceIds::FR);
        $this->assertEquals('EBAY-DE', MarketplaceIds::DE);
        $this->assertEquals('EBAY-US.MOTORS', MarketplaceIds::MOTORS);
        $this->assertEquals('EBAY-IT', MarketplaceIds::IT);
        $this->assertEquals('EBAY-NL', MarketplaceIds::NL);
        $this->assertEquals('EBAY-ES', MarketplaceIds::ES);
        $this->assertEquals('EBAY-CH', MarketplaceIds::CH);
        $this->assertEquals('EBAY-HK', MarketplaceIds::HK);
        $this->assertEquals('EBAY-IN', MarketplaceIds::IN);
        $this->assertEquals('EBAY-IE', MarketplaceIds::IE);
        $this->assertEquals('EBAY-MY', MarketplaceIds::MY);
        $this->assertEquals('EBAY-PH', MarketplaceIds::PH);
        $this->assertEquals('EBAY-PL', MarketplaceIds::PL);
        $this->assertEquals('EBAY-SG', MarketplaceIds::SG);
        $this->assertEquals('EBAY-CN', MarketplaceIds::CN);
    }
}
