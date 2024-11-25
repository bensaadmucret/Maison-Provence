<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\ProductSEO;
use PHPUnit\Framework\TestCase;

class ProductSEOTest extends TestCase
{
    private ProductSEO $productSEO;

    protected function setUp(): void
    {
        $this->productSEO = new ProductSEO();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->productSEO->getMetaTitle());
        $this->assertNull($this->productSEO->getMetaDescription());
        $this->assertNull($this->productSEO->getCanonicalUrl());
        $this->assertIsArray($this->productSEO->getMetaKeywords());
        $this->assertEmpty($this->productSEO->getMetaKeywords());
        $this->assertTrue($this->productSEO->isIndexable());
        $this->assertTrue($this->productSEO->isFollowable());
        $this->assertIsArray($this->productSEO->getOpenGraphData());
        $this->assertEmpty($this->productSEO->getOpenGraphData());
    }

    public function testBasicSettersAndGetters(): void
    {
        $title = 'Test Product';
        $description = 'Test Description';
        $url = 'https://example.com/product';
        $keywords = ['test', 'product'];

        $this->productSEO->setMetaTitle($title);
        $this->productSEO->setMetaDescription($description);
        $this->productSEO->setCanonicalUrl($url);
        $this->productSEO->setMetaKeywords($keywords);

        $this->assertEquals($title, $this->productSEO->getMetaTitle());
        $this->assertEquals($description, $this->productSEO->getMetaDescription());
        $this->assertEquals($url, $this->productSEO->getCanonicalUrl());
        $this->assertEquals($keywords, $this->productSEO->getMetaKeywords());
    }

    public function testProductAssociation(): void
    {
        $product = new Product();

        $this->productSEO->setProduct($product);
        $this->assertSame($product, $this->productSEO->getProduct());
    }
}
