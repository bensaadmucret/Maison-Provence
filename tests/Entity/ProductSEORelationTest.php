<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductSEO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductSEORelationTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->slugger = static::getContainer()->get(SluggerInterface::class);
    }

    protected function tearDown(): void
    {
        // Nettoyer la base de données après chaque test
        $connection = $this->entityManager->getConnection();
        
        // Désactiver les contraintes de clé étrangère
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Supprimer les données de manière sécurisée
        $connection->executeStatement('DELETE FROM product');
        $connection->executeStatement('DELETE FROM seo');
        $connection->executeStatement('DELETE FROM category');
        
        // Réactiver les contraintes de clé étrangère
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->entityManager->clear();
        parent::tearDown();
    }

    private function createTestCategory(): Category
    {
        $category = new Category();
        $category->setName('Catégorie de Test');
        $category->setSlug('categorie-de-test');
        $category->setDescription('Description de la catégorie de test');
        
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        
        return $category;
    }

    public function testProductSEOBidirectionalRelationship()
    {
        // Créer une catégorie unique
        $category = new Category();
        $category->setName('Catégorie Test ' . uniqid());
        $category->setSlug('categorie-test-' . uniqid());
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer un produit
        $product = new Product();
        $product->setName('Produit Test ' . uniqid());
        $product->setDescription('Description du produit');
        $product->setPrice(99.99);
        $product->setStock(10);
        $product->setCategory($category);

        // Créer un SEO pour ce produit
        $productSEO = new ProductSEO();
        $productSEO->setMetaTitle('Titre SEO Test ' . uniqid());
        $productSEO->setMetaDescription('Description SEO Test');

        // Établir la relation bidirectionnelle
        $product->setSeo($productSEO);

        // Persister les entités
        $this->entityManager->persist($product);
        $this->entityManager->persist($productSEO);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Recharger le produit
        $reloadedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['name' => $product->getName()]);

        // Vérifier la relation
        $this->assertNotNull($reloadedProduct);
        $this->assertNotNull($reloadedProduct->getSeo());
        $this->assertEquals($productSEO->getMetaTitle(), $reloadedProduct->getSeo()->getMetaTitle());
    }

    public function testProductSEONullRelationship()
    {
        // Créer un produit sans catégorie
        $product = new Product();
        $product->setName('Produit Sans Catégorie ' . uniqid());
        $product->setDescription('Description du produit');
        $product->setPrice(79.99);
        $product->setStock(5);
        $product->setCategory(null);  // Définir explicitement la catégorie comme null
        $product->setSeo(null);  // Explicitement définir le SEO à null

        try {
            $this->entityManager->persist($product);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->fail("Erreur lors de la sauvegarde du produit : " . $e->getMessage());
        }

        // Recharger le produit
        $this->entityManager->clear();
        $reloadedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['name' => $product->getName()]);

        // Vérifications
        $this->assertNotNull($reloadedProduct, 'Le produit n\'a pas été sauvegardé');
        $this->assertNull($reloadedProduct->getCategory(), 'La catégorie devrait être nulle');
        $this->assertNull($reloadedProduct->getSeo(), 'Le SEO devrait être nul');
        $this->assertEquals($product->getName(), $reloadedProduct->getName(), 'Le nom du produit est incorrect');
        $this->assertNotNull($reloadedProduct->getSlug(), 'Un slug doit être généré automatiquement');
    }

    public function testProductSEOReplacementScenario()
    {
        // Créer une catégorie unique
        $category = new Category();
        $category->setName('Catégorie Test ' . uniqid());
        $category->setSlug('categorie-test-' . uniqid());
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer un produit avec un premier SEO
        $product = new Product();
        $product->setName('Produit Original ' . uniqid());
        $product->setDescription('Description originale');
        $product->setPrice(129.99);
        $product->setStock(3);
        $product->setCategory($category);

        $originalSEO = new ProductSEO();
        $originalSEO->setMetaTitle('Titre Original ' . uniqid());
        $product->setSeo($originalSEO);

        // Persister les entités
        $this->entityManager->persist($product);
        $this->entityManager->persist($originalSEO);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Créer un nouveau SEO et le remplacer
        $newSEO = new ProductSEO();
        $newSEO->setMetaTitle('Nouveau Titre ' . uniqid());

        // Recharger le produit
        $reloadedProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['name' => $product->getName()]);

        // Remplacer le SEO
        $reloadedProduct->setSeo($newSEO);
        $this->entityManager->persist($reloadedProduct);
        $this->entityManager->persist($newSEO);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Vérifier le remplacement
        $finalProduct = $this->entityManager->getRepository(Product::class)
            ->findOneBy(['name' => $product->getName()]);

        $this->assertNotNull($finalProduct->getSeo());
        $this->assertEquals($newSEO->getMetaTitle(), $finalProduct->getSeo()->getMetaTitle());
    }
}
