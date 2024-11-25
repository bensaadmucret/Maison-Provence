<?php

namespace App\Tests\Entity;

use App\Entity\SiteConfiguration;
use PHPUnit\Framework\TestCase;

class SiteConfigurationTest extends TestCase
{
    private SiteConfiguration $siteConfig;

    protected function setUp(): void
    {
        $this->siteConfig = new SiteConfiguration();
    }

    public function testEcommerceEnabledDefaultValue(): void
    {
        $this->assertTrue($this->siteConfig->isEcommerceEnabled(), 'E-commerce should be enabled by default');
    }

    public function testEcommerceEnabledCanBeChanged(): void
    {
        $this->siteConfig->setIsEcommerceEnabled(false);
        $this->assertFalse($this->siteConfig->isEcommerceEnabled());
    }

    public function testEcommerceDisabledMessage(): void
    {
        $message = 'Notre boutique est temporairement fermée';
        $this->siteConfig->setEcommerceDisabledMessage($message);
        $this->assertEquals($message, $this->siteConfig->getEcommerceDisabledMessage());
    }
}
