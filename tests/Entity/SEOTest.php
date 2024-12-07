<?php

namespace App\Tests\Entity;

use App\Entity\SEO;
use PHPUnit\Framework\TestCase;

/**
 * Concrete implementation of SEO for testing.
 */
class TestSEO extends SEO
{
    // No additional implementation needed as we're just testing the base class functionality
}

class SEOTest extends TestCase
{
    private SEO $seo;

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

    public function testToArrayReturnsExpectedStructure(): void
    {
        $seo = new TestSEO();
        $seo->setMetaTitle('Test Title');
        $seo->setMetaDescription('Test Description');
        $seo->setMetaKeywords(['test', 'keywords']);
        $seo->setIndexable(true);
        $seo->setFollowable(true);

        $data = [
            'metaTitle' => $seo->getMetaTitle(),
            'metaDescription' => $seo->getMetaDescription(),
            'metaKeywords' => $seo->getMetaKeywords(),
            'indexable' => $seo->isIndexable(),
            'followable' => $seo->isFollowable(),
        ];

        $this->assertIsArray($data);
        $this->assertArrayHasKey('metaTitle', $data);
        $this->assertArrayHasKey('metaDescription', $data);
        $this->assertArrayHasKey('metaKeywords', $data);
        $this->assertArrayHasKey('indexable', $data);
        $this->assertArrayHasKey('followable', $data);
    }

    public function testMetaTitleMaxLength(): void
    {
        $longTitle = str_repeat('a', 80);
        $this->seo->setMetaTitle($longTitle);
        $this->assertLessThanOrEqual(70, strlen((string) $this->seo->getMetaTitle()));
    }

    public function testMetaDescriptionMaxLength(): void
    {
        $longDescription = str_repeat('a', 250);
        $this->seo->setMetaDescription($longDescription);
        $this->assertLessThanOrEqual(200, strlen((string) $this->seo->getMetaDescription()));
    }

    public function testMetaKeywordsUnique(): void
    {
        $keywords = ['test', 'test', 'unique'];
        $this->seo->setMetaKeywords($keywords);

        $actualKeywords = array_unique($this->seo->getMetaKeywords());
        $this->assertCount(2, $actualKeywords);
        $this->assertContains('test', $actualKeywords);
        $this->assertContains('unique', $actualKeywords);
    }

    public function testOpenGraphDataValidation(): void
    {
        $ogData = [
            'title' => 'Test Title',
            'description' => 'Test Description',
            'image' => 'https://example.com/image.jpg',
            'type' => 'website',
        ];

        $this->seo->setOpenGraphData($ogData);
        $this->assertArrayHasKey('title', $this->seo->getOpenGraphData());
        $this->assertArrayHasKey('description', $this->seo->getOpenGraphData());
        $this->assertArrayHasKey('image', $this->seo->getOpenGraphData());
        $this->assertArrayHasKey('type', $this->seo->getOpenGraphData());
    }

    public function testMetaDescriptionLength(): void
    {
        $this->seo->setMetaDescription('Description');
        $this->assertLessThanOrEqual(200, strlen((string) $this->seo->getMetaDescription()));
    }

    public function testMetaTitleLength(): void
    {
        $this->seo->setMetaTitle('Title');
        $this->assertLessThanOrEqual(70, strlen((string) $this->seo->getMetaTitle()));
    }

    public function testMetaKeywords(): void
    {
        $keywords = ['test', 'keywords'];
        $this->seo->setMetaKeywords($keywords);
        self::assertSame($keywords, $this->seo->getMetaKeywords());
    }

    public function testMetaDescription(): void
    {
        $description = 'Test description';
        $this->seo->setMetaDescription($description);
        self::assertSame($description, $this->seo->getMetaDescription());
    }

    public function testMetaTitle(): void
    {
        $title = 'Test title';
        $this->seo->setMetaTitle($title);
        self::assertSame($title, $this->seo->getMetaTitle());
    }

    public function testCanonicalUrl(): void
    {
        $url = 'https://example.com';
        $this->seo->setCanonicalUrl($url);
        self::assertSame($url, $this->seo->getCanonicalUrl());
    }

    public function testIndexableAndFollowable(): void
    {
        $this->seo->setIndexable(false);
        $this->seo->setFollowable(false);

        self::assertFalse($this->seo->isIndexable());
        self::assertFalse($this->seo->isFollowable());
    }
}
