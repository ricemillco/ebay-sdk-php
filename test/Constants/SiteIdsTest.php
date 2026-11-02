<?php
namespace DTS\eBaySDK\Constants\Test;

use DTS\eBaySDK\Constants\SiteIds as SiteIds;

class SiteIdsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantsAreCorrectType()
    {
        $this->assertIsInt(SiteIds::US);
        $this->assertIsInt(SiteIds::ENCA);
        $this->assertIsInt(SiteIds::GB);
        $this->assertIsInt(SiteIds::AU);
        $this->assertIsInt(SiteIds::AT);
        $this->assertIsInt(SiteIds::FRBE);
        $this->assertIsInt(SiteIds::FR);
        $this->assertIsInt(SiteIds::DE);
        $this->assertIsInt(SiteIds::MOTORS);
        $this->assertIsInt(SiteIds::IT);
        $this->assertIsInt(SiteIds::NLBE);
        $this->assertIsInt(SiteIds::NL);
        $this->assertIsInt(SiteIds::ES);
        $this->assertIsInt(SiteIds::CH);
        $this->assertIsInt(SiteIds::HK);
        $this->assertIsInt(SiteIds::IN);
        $this->assertIsInt(SiteIds::IE);
        $this->assertIsInt(SiteIds::MY);
        $this->assertIsInt(SiteIds::FRCA);
        $this->assertIsInt(SiteIds::PH);
        $this->assertIsInt(SiteIds::PL);
        $this->assertIsInt(SiteIds::SG);
    }

    public function testConstantsHaveCorrectValue()
    {
        $this->assertEquals(0, SiteIds::US);
        $this->assertEquals(2, SiteIds::ENCA);
        $this->assertEquals(3, SiteIds::GB);
        $this->assertEquals(15, SiteIds::AU);
        $this->assertEquals(16, SiteIds::AT);
        $this->assertEquals(23, SiteIds::FRBE);
        $this->assertEquals(71, SiteIds::FR);
        $this->assertEquals(77, SiteIds::DE);
        $this->assertEquals(100, SiteIds::MOTORS);
        $this->assertEquals(101, SiteIds::IT);
        $this->assertEquals(123, SiteIds::NLBE);
        $this->assertEquals(146, SiteIds::NL);
        $this->assertEquals(186, SiteIds::ES);
        $this->assertEquals(193, SiteIds::CH);
        $this->assertEquals(201, SiteIds::HK);
        $this->assertEquals(203, SiteIds::IN);
        $this->assertEquals(205, SiteIds::IE);
        $this->assertEquals(207, SiteIds::MY);
        $this->assertEquals(210, SiteIds::FRCA);
        $this->assertEquals(211, SiteIds::PH);
        $this->assertEquals(212, SiteIds::PL);
        $this->assertEquals(216, SiteIds::SG);
    }
}
