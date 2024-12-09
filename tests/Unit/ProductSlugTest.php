<?php

namespace App\Tests\Unit;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductSlugTest extends TestCase
{
    private AsciiSlugger $slugger;

    protected function setUp(): void
    {
        $this->slugger = new AsciiSlugger('fr');
    }

    private function createSlug(string $input): string
    {
        return strtolower($this->slugger->slug($input)->toString());
    }

    public function testSlugGenerationFromName()
    {
        $product = new Product();
        $product->setName('Canapé Design Moderne');

        $expectedSlug = $this->createSlug('Canapé Design Moderne');
        $this->assertEquals('canape-design-moderne', $expectedSlug);
    }

    public function testSlugWithSpecialCharacters()
    {
        $input = 'Chaise Élégante & Confortable';
        $slug = $this->createSlug($input);
        
        $this->assertEquals('chaise-elegante-confortable', $slug);
        $this->assertStringNotContainsString('Élégante', $slug);
        $this->assertStringNotContainsString('&', $slug);
    }

    public function testEmptyNameSlugGeneration()
    {
        $product = new Product();
        $product->setDescription('Produit sans nom');

        $expectedSlug = 'produit-' . uniqid();
        $product->setSlug($expectedSlug);

        $this->assertStringStartsWith('produit-', $product->getSlug());
    }

    public function testSlugUniqueness()
    {
        $product1 = new Product();
        $product1->setName('Canapé Design');

        $product2 = new Product();
        $product2->setName('Canapé Design');

        $slug1 = $this->createSlug($product1->getName());
        $slug2 = $this->createSlug($product2->getName()) . '-1';

        $this->assertNotEquals($slug1, $slug2);
    }

    public function testUpdateProductSlug()
    {
        $product = new Product();
        $product->setName('Canapé Original');

        $originalSlug = $this->createSlug('Canapé Original');
        $this->assertEquals('canape-original', $originalSlug);

        $product->setName('Canapé Modifié');
        $updatedSlug = $this->createSlug('Canapé Modifié');
        $this->assertEquals('canape-modifie', $updatedSlug);
    }
}
