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

    public function testIndexAndFollowSetters(): void
    {
        // Tester les valeurs par défaut
        $this->assertTrue($this->productSEO->isIndexable());
        $this->assertTrue($this->productSEO->isFollowable());

        // Modifier les valeurs
        $this->productSEO->setIndexable(false);
        $this->productSEO->setFollowable(false);

        $this->assertFalse($this->productSEO->isIndexable());
        $this->assertFalse($this->productSEO->isFollowable());
    }

    public function testOpenGraphDataManagement(): void
    {
        // Ajouter des données Open Graph
        $ogData = [
            'og:title' => 'Awesome Product',
            'og:type' => 'product',
            'og:url' => 'https://example.com/product',
            'og:image' => 'https://example.com/product-image.jpg'
        ];

        $this->productSEO->setOpenGraphData($ogData);

        $this->assertEquals($ogData, $this->productSEO->getOpenGraphData());
    }

    public function testProductBidirectionalAssociation(): void
    {
        $product = new Product();
        $product->setName('Test Product');

        // Associer le ProductSEO au Product
        $this->productSEO->setProduct($product);

        // Vérifier l'association bidirectionnelle
        $this->assertSame($this->productSEO, $product->getSeo());
        $this->assertSame($product, $this->productSEO->getProduct());
    }

    public function testProductSEOReplacementScenario(): void
    {
        $product1 = new Product();
        $product1->setName('First Product');

        $product2 = new Product();
        $product2->setName('Second Product');

        // Associer le ProductSEO au premier produit
        $this->productSEO->setProduct($product1);
        $this->assertSame($this->productSEO, $product1->getSeo());

        // Réassocier le ProductSEO à un autre produit
        $this->productSEO->setProduct($product2);

        // Vérifier que le premier produit n'a plus de SEO
        $this->assertNull($product1->getSeo());

        // Vérifier la nouvelle association
        $this->assertSame($this->productSEO, $product2->getSeo());
        $this->assertSame($product2, $this->productSEO->getProduct());
    }

    public function testNullProductAssociation(): void
    {
        // Vérifier que l'association à null fonctionne
        $this->productSEO->setProduct(null);
        $this->assertNull($this->productSEO->getProduct());
    }

    public function testSEOMetadataValidation(): void
    {
        // Tester les limites de longueur et le formatage des métadonnées
        $longTitle = str_repeat('A', 300);
        $longDescription = str_repeat('B', 500);

        $this->productSEO->setMetaTitle($longTitle);
        $this->productSEO->setMetaDescription($longDescription);

        // Vérifier que les métadonnées sont tronquées ou gérées correctement
        $this->assertLessThanOrEqual(255, strlen($this->productSEO->getMetaTitle()));
        $this->assertLessThanOrEqual(500, strlen($this->productSEO->getMetaDescription()));
    }

    public function testOpenGraphDataEdgeCases(): void
    {
        // Tester l'ajout, la modification et la suppression de données Open Graph
        $initialData = [
            'og:title' => 'Initial Title',
            'og:type' => 'product'
        ];

        $this->productSEO->setOpenGraphData($initialData);
        $this->assertEquals($initialData, $this->productSEO->getOpenGraphData());

        // Modifier une partie des données
        $updatedData = $this->productSEO->getOpenGraphData();
        $updatedData['og:url'] = 'https://example.com/updated';
        $this->productSEO->setOpenGraphData($updatedData);

        $this->assertArrayHasKey('og:url', $this->productSEO->getOpenGraphData());
        $this->assertEquals('https://example.com/updated', $this->productSEO->getOpenGraphData()['og:url']);

        // Réinitialiser les données Open Graph
        $this->productSEO->setOpenGraphData([]);
        $this->assertEmpty($this->productSEO->getOpenGraphData());
    }

    public function testCanonicalUrlValidation(): void
    {
        // Tester différents formats d'URL canonique
        $validUrls = [
            'https://example.com/product',
            'http://test.com/page',
            '/relative/path',
            ''  // URL vide
        ];

        foreach ($validUrls as $url) {
            $this->productSEO->setCanonicalUrl($url);
            $this->assertEquals($url, $this->productSEO->getCanonicalUrl());
        }
    }

    public function testMetaKeywordsManagement(): void
    {
        // Tester l'ajout, la modification des mots-clés en utilisant setMetaKeywords
        $keywords = ['produit', 'test', 'seo'];
        $this->productSEO->setMetaKeywords($keywords);
        $this->assertEquals($keywords, $this->productSEO->getMetaKeywords());

        // Ajouter un mot-clé en recréant le tableau
        $updatedKeywords = array_merge($keywords, ['nouveau']);
        $this->productSEO->setMetaKeywords($updatedKeywords);
        $this->assertContains('nouveau', $this->productSEO->getMetaKeywords());

        // Supprimer un mot-clé en recréant le tableau
        $filteredKeywords = array_filter($updatedKeywords, function($keyword) {
            return $keyword !== 'test';
        });
        $this->productSEO->setMetaKeywords($filteredKeywords);
        $this->assertNotContains('test', $this->productSEO->getMetaKeywords());
    }

    public function testMetaKeywordsEdgeCases(): void
    {
        // Tester les cas limites des mots-clés
        $longKeywords = array_map(function($i) { 
            return str_repeat('A', 50) . $i; 
        }, range(1, 20));

        $this->productSEO->setMetaKeywords($longKeywords);
        
        // Vérifier que le nombre de mots-clés reste raisonnable
        $this->assertLessThanOrEqual(20, count($this->productSEO->getMetaKeywords()));
        
        // Vérifier la longueur des mots-clés
        foreach ($this->productSEO->getMetaKeywords() as $keyword) {
            $this->assertLessThanOrEqual(52, strlen($keyword));
        }
    }

    public function testOpenGraphDataValidation(): void
    {
        $validOpenGraphData = [
            'title' => 'Produit Fantastique',
            'description' => 'Une description courte',
            'image' => 'https://example.com/image.jpg',
            'type' => 'product'
        ];

        $this->productSEO->setOpenGraphData($validOpenGraphData);

        // Vérifier les contraintes de validation
        $this->assertArrayHasKey('title', $this->productSEO->getOpenGraphData());
        $this->assertArrayHasKey('description', $this->productSEO->getOpenGraphData());
        $this->assertArrayHasKey('image', $this->productSEO->getOpenGraphData());
        $this->assertArrayHasKey('type', $this->productSEO->getOpenGraphData());

        // Vérifier les longueurs maximales
        $this->assertLessThanOrEqual(70, strlen($this->productSEO->getOpenGraphData()['title']));
        $this->assertLessThanOrEqual(200, strlen($this->productSEO->getOpenGraphData()['description']));
    }
}
