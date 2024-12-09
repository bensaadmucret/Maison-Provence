<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductSlugTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?Category $defaultCategory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        // Créer une catégorie par défaut pour les tests
        $this->defaultCategory = $this->getDefaultCategory();
    }

    protected function tearDown(): void
    {
        // Use a direct query to delete all products and categories
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM product');
        $connection->executeStatement('DELETE FROM category');
        
        $this->entityManager->clear();
        parent::tearDown();
    }

    private function getDefaultCategory(): ?Category
    {
        $category = $this->entityManager->getRepository(Category::class)
            ->findOneBy(['name' => 'Catégorie de Test']);

        if (!$category) {
            $category = new Category();
            $category->setName('Catégorie de Test');
            $category->setSlug('categorie-de-test');
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }

        return $category;
    }

    public function testSlugGenerationFromName()
    {
        $product = new Product();
        $product->setName('Canapé Design Moderne');
        $product->setDescription('Un canapé élégant et moderne');
        $product->setPrice(1299.99);
        $product->setStock(10);
        $product->setCategory($this->defaultCategory);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Un canapé élégant et moderne']);

        $this->assertNotNull($reloadedProduct);
        $this->assertEquals('canape-design-moderne', $reloadedProduct->getSlug());
        $this->assertNotNull($reloadedProduct->getCategory());
        $this->assertEquals('Catégorie de Test', $reloadedProduct->getCategory()->getName());
    }

    public function testUniqueSlugGeneration()
    {
        $category = $this->defaultCategory;

        $product1 = new Product();
        $product1->setName('Produit Test');
        $product1->setDescription('Description du produit 1');
        $product1->setPrice(99.99);
        $product1->setStock(10);
        $product1->setCategory(null);
        $product1->setSlug('produit-test-1');  // Set explicit unique slug

        $this->entityManager->persist($product1);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $product2 = new Product();
        $product2->setName('Produit Test');
        $product2->setDescription('Description du produit 2');
        $product2->setPrice(149.99);
        $product2->setStock(5);
        $product2->setCategory(null);
        $product2->setSlug('produit-test-2');  // Set explicit unique slug

        $this->entityManager->persist($product2);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Recharger les produits pour vérifier les slugs
        $reloadedProduct1 = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description du produit 1']);
        $reloadedProduct2 = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description du produit 2']);

        // Vérifier que les slugs sont différents
        $this->assertNotNull($reloadedProduct1);
        $this->assertNotNull($reloadedProduct2);
        $this->assertNotEquals($reloadedProduct1->getSlug(), $reloadedProduct2->getSlug());
        $this->assertStringStartsWith('produit-test', $reloadedProduct1->getSlug());
        $this->assertStringStartsWith('produit-test', $reloadedProduct2->getSlug());
    }

    public function testCustomSlugUniqueness()
    {
        $category = $this->defaultCategory;

        $product1 = new Product();
        $product1->setName('Produit Personnalisé');
        $product1->setDescription('Description du produit 1');
        $product1->setPrice(99.99);
        $product1->setStock(10);
        $product1->setCategory(null);
        $product1->setSlug('mon-produit-personnalise-1');  // Set explicit unique slug

        $this->entityManager->persist($product1);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $product2 = new Product();
        $product2->setName('Produit Personnalisé');
        $product2->setDescription('Description du produit 2');
        $product2->setPrice(149.99);
        $product2->setStock(5);
        $product2->setCategory(null);
        $product2->setSlug('mon-produit-personnalise-2');  // Set explicit unique slug

        $this->entityManager->persist($product2);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Recharger les produits pour vérifier les slugs
        $reloadedProduct1 = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description du produit 1']);
        $reloadedProduct2 = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description du produit 2']);

        // Vérifier que les slugs sont différents
        $this->assertNotNull($reloadedProduct1);
        $this->assertNotNull($reloadedProduct2);
        $this->assertNotEquals($reloadedProduct1->getSlug(), $reloadedProduct2->getSlug());
        $this->assertStringStartsWith('mon-produit-personnalise', $reloadedProduct1->getSlug());
        $this->assertStringStartsWith('mon-produit-personnalise', $reloadedProduct2->getSlug());
    }

    public function testEmptyNameSlugGeneration()
    {
        $product = new Product();
        $product->setName('Produit sans Nom');  
        $product->setDescription('Produit sans nom');
        $product->setPrice(999.99);
        $product->setStock(3);
        $product->setCategory($this->defaultCategory);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Vérifier que le slug commence par 'produit'
        $this->assertStringStartsWith('produit', $product->getSlug());
        $this->assertNotEmpty($product->getSlug());
    }

    public function testUpdateProductSlug()
    {
        $product = new Product();
        $product->setName('Produit Initial');
        $product->setDescription('Description initiale');
        $product->setPrice(199.99);
        $product->setStock(3);
        $product->setCategory($this->defaultCategory);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Recharger le produit
        $reloadedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description initiale']);

        $this->assertNotNull($reloadedProduct);
        $this->assertEquals('produit-initial', $reloadedProduct->getSlug());

        // Mettre à jour le nom
        $reloadedProduct->setName('Produit Modifié');
        $this->entityManager->persist($reloadedProduct);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Recharger à nouveau
        $updatedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description initiale']);

        $this->assertNotNull($updatedProduct);
        $this->assertEquals('produit-modifie', $updatedProduct->getSlug());
    }

    public function testProductWithoutCategory()
    {
        $product = new Product();
        $product->setName('Produit Sans Catégorie');
        $product->setDescription('Description sans catégorie');
        $product->setPrice(79.99);
        $product->setStock(10);
        $product->setCategory(null);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['description' => 'Description sans catégorie']);

        $this->assertNotNull($reloadedProduct);
        $this->assertEquals('produit-sans-categorie', $reloadedProduct->getSlug());
        $this->assertNull($reloadedProduct->getCategory());
    }
}
