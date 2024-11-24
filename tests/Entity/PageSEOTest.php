<?php

namespace App\Tests\Entity;

use App\Entity\Page;
use App\Entity\PageSEO;
use PHPUnit\Framework\TestCase;

class PageSEOTest extends TestCase
{
    private PageSEO $pageSEO;

    protected function setUp(): void
    {
        $this->pageSEO = new PageSEO();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->pageSEO->getMetaTitle());
        $this->assertNull($this->pageSEO->getMetaDescription());
        $this->assertNull($this->pageSEO->getCanonicalUrl());
        $this->assertIsArray($this->pageSEO->getMetaKeywords());
        $this->assertEmpty($this->pageSEO->getMetaKeywords());
        $this->assertTrue($this->pageSEO->isIndexable());
        $this->assertTrue($this->pageSEO->isFollowable());
        $this->assertIsArray($this->pageSEO->getOpenGraphData());
        $this->assertEmpty($this->pageSEO->getOpenGraphData());
    }

    public function testBasicSettersAndGetters(): void
    {
        $title = 'Test Page';
        $description = 'Test Description';
        $url = 'https://example.com/page';
        $keywords = ['test', 'page'];

        $this->pageSEO->setMetaTitle($title);
        $this->pageSEO->setMetaDescription($description);
        $this->pageSEO->setCanonicalUrl($url);
        $this->pageSEO->setMetaKeywords($keywords);

        $this->assertEquals($title, $this->pageSEO->getMetaTitle());
        $this->assertEquals($description, $this->pageSEO->getMetaDescription());
        $this->assertEquals($url, $this->pageSEO->getCanonicalUrl());
        $this->assertEquals($keywords, $this->pageSEO->getMetaKeywords());
    }
}
