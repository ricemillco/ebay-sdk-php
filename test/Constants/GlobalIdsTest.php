<?php
namespace DTS\eBaySDK\Constants\Test;

use DTS\eBaySDK\Constants\GlobalIds as GlobalIds;

class GlobalIdsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantsAreCorrectType()
    {
        $this->assertIsString(GlobalIds::US);
        $this->assertIsString(GlobalIds::ENCA);
        $this->assertIsString(GlobalIds::GB);
        $this->assertIsString(GlobalIds::AU);
        $this->assertIsString(GlobalIds::AT);
        $this->assertIsString(GlobalIds::FRBE);
        $this->assertIsString(GlobalIds::FR);
        $this->assertIsString(GlobalIds::DE);
        $this->assertIsString(GlobalIds::MOTORS);
        $this->assertIsString(GlobalIds::IT);
        $this->assertIsString(GlobalIds::NLBE);
        $this->assertIsString(GlobalIds::NL);
        $this->assertIsString(GlobalIds::ES);
        $this->assertIsString(GlobalIds::CH);
        $this->assertIsString(GlobalIds::HK);
        $this->assertIsString(GlobalIds::IN);
        $this->assertIsString(GlobalIds::IE);
        $this->assertIsString(GlobalIds::MY);
        $this->assertIsString(GlobalIds::FRCA);
        $this->assertIsString(GlobalIds::PH);
        $this->assertIsString(GlobalIds::PL);
        $this->assertIsString(GlobalIds::SG);
    }

    public function testConstantsHaveCorrectValue()
    {
        $this->assertEquals('EBAY-US', GlobalIds::US);
        $this->assertEquals('EBAY-ENCA', GlobalIds::ENCA);
        $this->assertEquals('EBAY-GB', GlobalIds::GB);
        $this->assertEquals('EBAY-AU', GlobalIds::AU);
        $this->assertEquals('EBAY-AT', GlobalIds::AT);
        $this->assertEquals('EBAY-FRBE', GlobalIds::FRBE);
        $this->assertEquals('EBAY-FR', GlobalIds::FR);
        $this->assertEquals('EBAY-DE', GlobalIds::DE);
        $this->assertEquals('EBAY-MOTOR', GlobalIds::MOTORS);
        $this->assertEquals('EBAY-IT', GlobalIds::IT);
        $this->assertEquals('EBAY-NLBE', GlobalIds::NLBE);
        $this->assertEquals('EBAY-NL', GlobalIds::NL);
        $this->assertEquals('EBAY-ES', GlobalIds::ES);
        $this->assertEquals('EBAY-CH', GlobalIds::CH);
        $this->assertEquals('EBAY-HK', GlobalIds::HK);
        $this->assertEquals('EBAY-IN', GlobalIds::IN);
        $this->assertEquals('EBAY-IE', GlobalIds::IE);
        $this->assertEquals('EBAY-MY', GlobalIds::MY);
        $this->assertEquals('EBAY-FRCA', GlobalIds::FRCA);
        $this->assertEquals('EBAY-PH', GlobalIds::PH);
        $this->assertEquals('EBAY-PL', GlobalIds::PL);
        $this->assertEquals('EBAY-SG', GlobalIds::SG);
    }
}
