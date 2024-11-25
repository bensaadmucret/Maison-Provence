<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\CategorySEO;
use PHPUnit\Framework\TestCase;

class CategorySEOTest extends TestCase
{
    private CategorySEO $categorySEO;

    protected function setUp(): void
    {
        $this->categorySEO = new CategorySEO();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->categorySEO->getMetaTitle());
        $this->assertNull($this->categorySEO->getMetaDescription());
        $this->assertNull($this->categorySEO->getCanonicalUrl());
        $this->assertIsArray($this->categorySEO->getMetaKeywords());
        $this->assertEmpty($this->categorySEO->getMetaKeywords());
        $this->assertTrue($this->categorySEO->isIndexable());
        $this->assertTrue($this->categorySEO->isFollowable());
        $this->assertIsArray($this->categorySEO->getOpenGraphData());
        $this->assertEmpty($this->categorySEO->getOpenGraphData());
    }

    public function testBasicSettersAndGetters(): void
    {
        $title = 'Test Category';
        $description = 'Test Description';
        $url = 'https://example.com/category';
        $keywords = ['test', 'category'];

        $this->categorySEO->setMetaTitle($title);
        $this->categorySEO->setMetaDescription($description);
        $this->categorySEO->setCanonicalUrl($url);
        $this->categorySEO->setMetaKeywords($keywords);

        $this->assertEquals($title, $this->categorySEO->getMetaTitle());
        $this->assertEquals($description, $this->categorySEO->getMetaDescription());
        $this->assertEquals($url, $this->categorySEO->getCanonicalUrl());
        $this->assertEquals($keywords, $this->categorySEO->getMetaKeywords());
    }

    public function testCategoryAssociation(): void
    {
        $category = new Category();

        $this->categorySEO->setCategory($category);
        $this->assertSame($category, $this->categorySEO->getCategory());
    }
}
