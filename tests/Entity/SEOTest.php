<?php

namespace App\Tests\Entity;

use App\Entity\SEO;
use PHPUnit\Framework\TestCase;

class SEOTest extends TestCase
{
    private TestSEO $seo;

    protected function setUp(): void
    {
        $this->seo = new TestSEO();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->seo->getMetaTitle());
        $this->assertNull($this->seo->getMetaDescription());
        $this->assertNull($this->seo->getCanonicalUrl());
        $this->assertIsArray($this->seo->getMetaKeywords());
        $this->assertEmpty($this->seo->getMetaKeywords());
        $this->assertTrue($this->seo->isIndexable());
        $this->assertTrue($this->seo->isFollowable());
        $this->assertIsArray($this->seo->getOpenGraphData());
        $this->assertEmpty($this->seo->getOpenGraphData());
    }

    public function testMetaTitleMaxLength(): void
    {
        $longTitle = str_repeat('a', 70);
        $this->seo->setMetaTitle($longTitle);
        $this->assertEquals(60, strlen($this->seo->getMetaTitle()));
    }

    public function testMetaDescriptionMaxLength(): void
    {
        $longDescription = str_repeat('a', 200);
        $this->seo->setMetaDescription($longDescription);
        $this->assertEquals(160, strlen($this->seo->getMetaDescription()));
    }

    public function testMetaKeywordsUnique(): void
    {
        $keywords = ['test', 'test', 'unique'];
        $this->seo->setMetaKeywords($keywords);
        
        $actualKeywords = $this->seo->getMetaKeywords();
        $this->assertCount(2, $actualKeywords);
        $this->assertContains('test', $actualKeywords);
        $this->assertContains('unique', $actualKeywords);
    }

    public function testOpenGraphDataValidation(): void
    {
        $ogData = [
            'invalid:title' => 'Test Title',
            'og:title' => 'Test Title',
            'og:description' => 'Test Description'
        ];

        $this->seo->setOpenGraphData($ogData);
        $this->assertArrayNotHasKey('invalid:title', $this->seo->getOpenGraphData());
        $this->assertArrayHasKey('og:title', $this->seo->getOpenGraphData());
        $this->assertArrayHasKey('og:description', $this->seo->getOpenGraphData());
    }
}

// Classe concrète pour tester la classe abstraite
class TestSEO extends SEO
{
}
