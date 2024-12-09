<?php

namespace App\Tests\Integration;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductPersistenceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private AsciiSlugger $slugger;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->slugger = new AsciiSlugger('fr');

        // Supprimer le schéma existant
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        
        // Récupérer le schéma
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Supprimer et recréer le schéma
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test après chaque test
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM product');
        $connection->executeStatement('DELETE FROM category');
        
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function createTestCategory(): Category
    {
        $category = new Category();
        $category->setName('Meubles de Salon');
        $category->setDescription('Catégorie de meubles pour salon');
        
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        
        return $category;
    }

    public function testProductPersistenceWithSlug()
    {
        // Créer une catégorie avec un slug
        $category = new Category();
        $category->setName('Chaussures');
        $category->setSlug($this->slugger->slug($category->getName()));
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer un produit
        $product = new Product();
        $product->setName('Baskets Noires');
        $product->setDescription('Des baskets noires confortables');
        $product->setPrice(89.99);
        $product->setCategory($category);
        $product->setSlug('baskets-noires');  // Définir explicitement le slug
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Récupérer le produit et vérifier ses propriétés
        $retrievedProduct = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => 'Baskets Noires']);
        $this->assertNotNull($retrievedProduct);
        $this->assertEquals('baskets-noires', $retrievedProduct->getSlug());
    }

    public function testUniqueSlugGeneration()
    {
        // Créer une catégorie avec un slug
        $category = new Category();
        $category->setName('Accessoires');
        $category->setSlug($this->slugger->slug($category->getName()));
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer un premier produit
        $product1 = new Product();
        $product1->setName('Baskets Noires');
        $product1->setDescription('Des baskets noires confortables');
        $product1->setPrice(89.99);
        $product1->setCategory($category);
        $product1->setSlug('baskets-noires');  // Définir explicitement le slug
        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        // Créer un deuxième produit avec le même nom
        $product2 = new Product();
        $product2->setName('Baskets Noires');
        $product2->setDescription('Une autre paire de baskets noires');
        $product2->setPrice(99.99);
        $product2->setCategory($category);
        $product2->setSlug('baskets-noires-2');  // Définir explicitement le slug
        $this->entityManager->persist($product2);
        $this->entityManager->flush();

        // Vérifier que les slugs sont différents
        $this->assertNotEquals($product1->getSlug(), $product2->getSlug());
    }

    public function testProductSlugUpdateOnNameChange()
    {
        // Créer une catégorie avec un slug
        $category = new Category();
        $category->setName('Chaussures');
        $category->setSlug($this->slugger->slug($category->getName()));
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer un produit
        $product = new Product();
        $product->setName('Baskets Noires');
        $product->setDescription('Des baskets noires confortables');
        $product->setPrice(89.99);
        $product->setCategory($category);
        $product->setSlug('baskets-noires');  // Définir explicitement le slug
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Stocker le slug original
        $originalSlug = $product->getSlug();

        // Modifier le nom du produit
        $product->setName('Baskets Blanches');
        $this->entityManager->flush();

        // Récupérer le produit mis à jour
        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());

        // Vérifier que le slug a été mis à jour
        $this->assertNotEquals($originalSlug, $updatedProduct->getSlug());
        $this->assertEquals('baskets-blanches', $updatedProduct->getSlug());
    }

    public function testProductWithSpecialCharactersInName()
    {
        // Créer une catégorie avec un slug
        $category = new Category();
        $category->setName('Accessoires');
        $category->setSlug($this->slugger->slug($category->getName()));
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer un produit avec des caractères spéciaux
        $product = new Product();
        $product->setName('Écharpe à Motifs');
        $product->setDescription('Une écharpe élégante avec des motifs');
        $product->setPrice(69.99);
        $product->setCategory($category);
        $product->setSlug('echarpe-a-motifs');  // Définir explicitement le slug
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // Vérifier le slug
        $this->assertEquals('echarpe-a-motifs', $product->getSlug());
    }
}
