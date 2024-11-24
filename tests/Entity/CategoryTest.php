<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\CategorySEO;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        $this->category = new Category();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->category->getId());
        $this->assertNull($this->category->getName());
        $this->assertNull($this->category->getDescription());
        $this->assertNull($this->category->getSlug());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->category->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->category->getUpdatedAt());
        $this->assertCount(0, $this->category->getProducts());
        $this->assertNull($this->category->getParent());
        $this->assertEquals(0, $this->category->getLevel());
    }

    public function testBasicSettersAndGetters(): void
    {
        $name = 'Test Category';
        $description = 'Test Description';
        $slug = 'test-category';

        $this->category->setName($name);
        $this->category->setDescription($description);
        $this->category->setSlug($slug);

        $this->assertEquals($name, $this->category->getName());
        $this->assertEquals($description, $this->category->getDescription());
        $this->assertEquals($slug, $this->category->getSlug());
    }

    public function testProductAssociation(): void
    {
        $product = new Product();
        
        $this->category->addProduct($product);
        $this->assertCount(1, $this->category->getProducts());
        $this->assertEquals($this->category, $product->getCategory());

        $this->category->removeProduct($product);
        $this->assertCount(0, $this->category->getProducts());
        $this->assertNull($product->getCategory());
    }

    public function testParentAssociation(): void
    {
        $parent = new Category();
        $parent->setName('Parent Category');

        $this->category->setParent($parent);
        $this->assertEquals($parent, $this->category->getParent());
        $this->assertEquals(1, $this->category->getLevel());

        $this->category->setParent(null);
        $this->assertNull($this->category->getParent());
        $this->assertEquals(0, $this->category->getLevel());
    }

    public function testSEOAssociation(): void
    {
        $seo = new CategorySEO();
        $seo->setCategory($this->category);
        
        $this->assertInstanceOf(CategorySEO::class, $this->category->getSeo());
        $this->assertEquals($seo, $this->category->getSeo());
        $this->assertEquals($this->category, $seo->getCategory());
    }
}
