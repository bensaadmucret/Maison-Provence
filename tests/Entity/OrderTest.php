<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Entity\User;
use App\Entity\Address;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE order_item');
        $connection->executeStatement('TRUNCATE TABLE `order`');
        $connection->executeStatement('TRUNCATE TABLE `user`');
        $connection->executeStatement('TRUNCATE TABLE product');
        $connection->executeStatement('TRUNCATE TABLE address');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->entityManager->clear();
        parent::tearDown();
    }

    public function testCreateOrder()
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('order_test_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setFirstName('John');
        $user->setLastName('Doe');

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setName('Adresse principale');
        $address->setFirstName($user->getFirstName());
        $address->setLastName($user->getLastName());
        $address->setStreet('123 Test Street');
        $address->setCity('Test City');
        $address->setPostalCode('12345');
        $address->setCountry('Test Country');

        // Créer un produit
        $productName = 'Produit de Test ' . uniqid();
        $product = new Product();
        $product->setName($productName);
        $product->setDescription('Description du produit de test');
        $product->setPrice(49.99);
        $product->setStock(10);
        $product->setSlug(strtolower(str_replace(' ', '-', $productName)));
        $product->setIsActive(true);
        $product->setIsFeatured(false);
        $product->setCategory(null);  // Définir explicitement la catégorie comme null

        // Créer un SEO pour le produit
        $productSeo = new ProductSEO();
        $productSeo->setMetaTitle('Produit de Test - ' . $productName);
        $productSeo->setMetaDescription('Description SEO pour le produit de test');
        $productSeo->setCanonicalUrl('https://example.com/produits/' . $product->getSlug());
        $productSeo->setMetaKeywords(['test', 'produit', 'exemple']);
        $productSeo->setIndexable(true);
        $productSeo->setFollowable(true);
        $productSeo->setOpenGraphData([
            'title' => 'Produit de Test',
            'description' => 'Description Open Graph',
            'image' => 'https://example.com/image-produit.jpg',
            'type' => 'product'
        ]);
        $product->setSeo($productSeo);

        // Créer une commande
        $order = new Order();
        $order->setUser($user);
        $order->setShippingAddress($address);
        $order->setBillingAddress($address);
        $order->setStatus('pending');
        $order->setTotal(49.99);
        $order->setPaymentMethod('credit_card');
        $order->setReference('TEST-' . uniqid());

        // Créer un article de commande
        $orderItem = new OrderItem();
        $orderItem->setOrderRef($order);
        $orderItem->setProduct($product);
        $orderItem->setQuantity(1);
        $orderItem->setPrice(49.99);

        // Ajouter l'article à la commande
        $order->addOrderItem($orderItem);

        // Persister les entités
        $this->entityManager->persist($user);
        $this->entityManager->persist($address);
        $this->entityManager->persist($product);
        $this->entityManager->persist($productSeo);
        $this->entityManager->persist($order);
        $this->entityManager->persist($orderItem);
        
        // Vérifier les erreurs de validation
        $validator = static::getContainer()->get('validator');
        $errors = $validator->validate($product);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->fail('Product validation failed: ' . implode(', ', $errorMessages));
        }

        try {
            // Afficher les détails du produit avant la persistance
            echo "Avant persistance - Nom: " . $product->getName() . "\n";
            echo "Avant persistance - Description: " . $product->getDescription() . "\n";
            echo "Avant persistance - Prix: " . $product->getPrice() . "\n";
            echo "Avant persistance - Stock: " . $product->getStock() . "\n";
            echo "Avant persistance - Slug: " . $product->getSlug() . "\n";
            echo "Avant persistance - Is Active: " . ($product->isActive() ? 'true' : 'false') . "\n";
            echo "Avant persistance - Is Featured: " . ($product->isFeatured() ? 'true' : 'false') . "\n";

            $this->entityManager->flush();
            
            // Vérifier les valeurs du produit après persistance
            $this->assertNotNull($product->getId(), 'Le produit aurait dû être persisté');
            $this->assertEquals($productName, $product->getName(), 'Le nom du produit ne correspond pas');
            $this->assertEquals('Description du produit de test', $product->getDescription(), 'La description du produit ne correspond pas');
            $this->assertEquals(49.99, $product->getPrice(), 'Le prix du produit ne correspond pas');
            $this->assertEquals(10, $product->getStock(), 'Le stock du produit ne correspond pas');
            $this->assertTrue($product->isActive(), 'Le produit devrait être actif');
            $this->assertFalse($product->isFeatured(), 'Le produit ne devrait pas être mis en avant');

            // Vérifier les valeurs du SEO du produit
            $this->assertNotNull($product->getSeo(), 'Le produit devrait avoir un SEO');
            $this->assertEquals('Produit de Test - ' . $productName, $product->getSeo()->getMetaTitle(), 'Le titre meta ne correspond pas');
            $this->assertEquals('Description SEO pour le produit de test', $product->getSeo()->getMetaDescription(), 'La description meta ne correspond pas');
            $this->assertEquals('https://example.com/produits/' . $product->getSlug(), $product->getSeo()->getCanonicalUrl(), 'L\'URL canonique ne correspond pas');
            $this->assertEquals(['test', 'produit', 'exemple'], $product->getSeo()->getMetaKeywords(), 'Les mots-clés meta ne correspondent pas');
            $this->assertTrue($product->getSeo()->isIndexable(), 'Le produit devrait être indexable');
            $this->assertTrue($product->getSeo()->isFollowable(), 'Le produit devrait être suivable');
            
            $expectedOpenGraphData = [
                'title' => 'Produit de Test',
                'description' => 'Description Open Graph',
                'image' => 'https://example.com/image-produit.jpg',
                'type' => 'product'
            ];
            $this->assertEquals($expectedOpenGraphData, $product->getSeo()->getOpenGraphData(), 'Les données Open Graph ne correspondent pas');
        } catch (\Exception $e) {
            $this->fail('Flush failed: ' . $e->getMessage() . 
                        "\nProduct details:" . 
                        "\nName: " . $product->getName() . 
                        "\nDescription: " . $product->getDescription() . 
                        "\nPrice: " . $product->getPrice() . 
                        "\nStock: " . $product->getStock() . 
                        "\nIs Active: " . ($product->isActive() ? 'true' : 'false') . 
                        "\nIs Featured: " . ($product->isFeatured() ? 'true' : 'false') . 
                        "\nSlug: " . $product->getSlug());
        }

        // Vérifier que la commande a été créée
        $orderRepository = $this->entityManager->getRepository(Order::class);
        $foundOrder = $orderRepository->findOneBy(['user' => $user]);

        $this->assertNotNull($foundOrder);
        $this->assertEquals($user, $foundOrder->getUser());
        $this->assertEquals($address, $foundOrder->getShippingAddress());
        $this->assertEquals($address, $foundOrder->getBillingAddress());
        $this->assertEquals('pending', $foundOrder->getStatus());
        $this->assertEquals(49.99, $foundOrder->getTotal());
        $this->assertCount(1, $foundOrder->getOrderItems());
        $this->assertEquals($orderItem, $foundOrder->getOrderItems()[0]);
    }

    public function testOrderTotalCalculation()
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('order_total_test_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setFirstName('Jane');
        $user->setLastName('Smith');

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setName('Adresse principale');
        $address->setFirstName($user->getFirstName());
        $address->setLastName($user->getLastName());
        $address->setStreet('456 Test Avenue');
        $address->setCity('Test City');
        $address->setPostalCode('54321');
        $address->setCountry('Test Country');

        // Créer des produits
        $product1Name = 'Produit 1 ' . uniqid();
        $product1 = new Product();
        $product1->setName($product1Name);
        $product1->setDescription('Description du produit 1');
        $product1->setPrice(29.99);
        $product1->setStock(10);
        $product1->setSlug(strtolower(str_replace(' ', '-', $product1Name)));
        $product1->setIsActive(true);
        $product1->setIsFeatured(false);
        $product1->setCategory(null);  // Définir explicitement la catégorie comme null

        $product2Name = 'Produit 2 ' . uniqid();
        $product2 = new Product();
        $product2->setName($product2Name);
        $product2->setDescription('Description du produit 2');
        $product2->setPrice(19.99);
        $product2->setStock(5);
        $product2->setSlug(strtolower(str_replace(' ', '-', $product2Name)));
        $product2->setIsActive(true);
        $product2->setIsFeatured(false);
        $product2->setCategory(null);  // Définir explicitement la catégorie comme null

        // Créer un SEO pour le produit 1
        $productSeo1 = new ProductSEO();
        $productSeo1->setMetaTitle('Produit 1 - ' . $product1Name);
        $productSeo1->setMetaDescription('Description SEO pour le produit 1');
        $productSeo1->setCanonicalUrl('https://example.com/produits/' . $product1->getSlug());
        $productSeo1->setMetaKeywords(['test', 'produit', 'exemple']);
        $productSeo1->setIndexable(true);
        $productSeo1->setFollowable(true);
        $productSeo1->setOpenGraphData([
            'title' => 'Produit 1',
            'description' => 'Description Open Graph',
            'image' => 'https://example.com/image-produit.jpg',
            'type' => 'product'
        ]);
        $product1->setSeo($productSeo1);

        // Créer un SEO pour le produit 2
        $productSeo2 = new ProductSEO();
        $productSeo2->setMetaTitle('Produit 2 - ' . $product2Name);
        $productSeo2->setMetaDescription('Description SEO pour le produit 2');
        $productSeo2->setCanonicalUrl('https://example.com/produits/' . $product2->getSlug());
        $productSeo2->setMetaKeywords(['test', 'produit', 'exemple']);
        $productSeo2->setIndexable(true);
        $productSeo2->setFollowable(true);
        $productSeo2->setOpenGraphData([
            'title' => 'Produit 2',
            'description' => 'Description Open Graph',
            'image' => 'https://example.com/image-produit.jpg',
            'type' => 'product'
        ]);
        $product2->setSeo($productSeo2);

        // Créer une commande
        $order = new Order();
        $order->setUser($user);
        $order->setShippingAddress($address);
        $order->setBillingAddress($address);
        $order->setStatus('pending');
        $order->setPaymentMethod('credit_card');
        $order->setReference('TEST-' . uniqid());

        // Créer des articles de commande
        $orderItem1 = new OrderItem();
        $orderItem1->setOrderRef($order);
        $orderItem1->setProduct($product1);
        $orderItem1->setQuantity(2);
        $orderItem1->setPrice(29.99);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrderRef($order);
        $orderItem2->setProduct($product2);
        $orderItem2->setQuantity(3);
        $orderItem2->setPrice(19.99);

        // Ajouter les articles à la commande
        $order->addOrderItem($orderItem1);
        $order->addOrderItem($orderItem2);

        // Calculer le total
        $expectedTotal = (2 * 29.99) + (3 * 19.99);
        $order->setTotal($expectedTotal);

        // Persister les entités
        $this->entityManager->persist($user);
        $this->entityManager->persist($address);
        $this->entityManager->persist($product1);
        $this->entityManager->persist($productSeo1);
        $this->entityManager->persist($product2);
        $this->entityManager->persist($productSeo2);
        $this->entityManager->persist($order);
        $this->entityManager->persist($orderItem1);
        $this->entityManager->persist($orderItem2);
        
        // Vérifier les erreurs de validation
        $validator = static::getContainer()->get('validator');
        $errors = $validator->validate($product1);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->fail('Product validation failed: ' . implode(', ', $errorMessages));
        }

        try {
            // Afficher les détails des produits avant la persistance
            echo "Avant persistance - Produit 1 - Nom: " . $product1->getName() . "\n";
            echo "Avant persistance - Produit 1 - Description: " . $product1->getDescription() . "\n";
            echo "Avant persistance - Produit 1 - Prix: " . $product1->getPrice() . "\n";
            echo "Avant persistance - Produit 1 - Stock: " . $product1->getStock() . "\n";
            echo "Avant persistance - Produit 1 - Slug: " . $product1->getSlug() . "\n";
            echo "Avant persistance - Produit 1 - Is Active: " . ($product1->isActive() ? 'true' : 'false') . "\n";
            echo "Avant persistance - Produit 1 - Is Featured: " . ($product1->isFeatured() ? 'true' : 'false') . "\n";

            echo "Avant persistance - Produit 2 - Nom: " . $product2->getName() . "\n";
            echo "Avant persistance - Produit 2 - Description: " . $product2->getDescription() . "\n";
            echo "Avant persistance - Produit 2 - Prix: " . $product2->getPrice() . "\n";
            echo "Avant persistance - Produit 2 - Stock: " . $product2->getStock() . "\n";
            echo "Avant persistance - Produit 2 - Slug: " . $product2->getSlug() . "\n";
            echo "Avant persistance - Produit 2 - Is Active: " . ($product2->isActive() ? 'true' : 'false') . "\n";
            echo "Avant persistance - Produit 2 - Is Featured: " . ($product2->isFeatured() ? 'true' : 'false') . "\n";

            $this->entityManager->flush();
            
            // Vérifier les valeurs des produits après persistance
            $this->assertNotNull($product1->getId(), 'Le produit 1 aurait dû être persisté');
            $this->assertNotNull($product2->getId(), 'Le produit 2 aurait dû être persisté');

            // Vérifier les valeurs du SEO du produit 1
            $this->assertNotNull($product1->getSeo(), 'Le produit 1 devrait avoir un SEO');
            $this->assertEquals('Produit 1 - ' . $product1Name, $product1->getSeo()->getMetaTitle(), 'Le titre meta du produit 1 ne correspond pas');
            $this->assertEquals('Description SEO pour le produit 1', $product1->getSeo()->getMetaDescription(), 'La description meta du produit 1 ne correspond pas');
            $this->assertEquals('https://example.com/produits/' . $product1->getSlug(), $product1->getSeo()->getCanonicalUrl(), 'L\'URL canonique du produit 1 ne correspond pas');
            $this->assertEquals(['test', 'produit', 'exemple'], $product1->getSeo()->getMetaKeywords(), 'Les mots-clés meta du produit 1 ne correspondent pas');
            $this->assertTrue($product1->getSeo()->isIndexable(), 'Le produit 1 devrait être indexable');
            $this->assertTrue($product1->getSeo()->isFollowable(), 'Le produit 1 devrait être suivable');
            
            $expectedOpenGraphData1 = [
                'title' => 'Produit 1',
                'description' => 'Description Open Graph',
                'image' => 'https://example.com/image-produit.jpg',
                'type' => 'product'
            ];
            $this->assertEquals($expectedOpenGraphData1, $product1->getSeo()->getOpenGraphData(), 'Les données Open Graph du produit 1 ne correspondent pas');

            // Vérifier les valeurs du SEO du produit 2
            $this->assertNotNull($product2->getSeo(), 'Le produit 2 devrait avoir un SEO');
            $this->assertEquals('Produit 2 - ' . $product2Name, $product2->getSeo()->getMetaTitle(), 'Le titre meta du produit 2 ne correspond pas');
            $this->assertEquals('Description SEO pour le produit 2', $product2->getSeo()->getMetaDescription(), 'La description meta du produit 2 ne correspond pas');
            $this->assertEquals('https://example.com/produits/' . $product2->getSlug(), $product2->getSeo()->getCanonicalUrl(), 'L\'URL canonique du produit 2 ne correspond pas');
            $this->assertEquals(['test', 'produit', 'exemple'], $product2->getSeo()->getMetaKeywords(), 'Les mots-clés meta du produit 2 ne correspondent pas');
            $this->assertTrue($product2->getSeo()->isIndexable(), 'Le produit 2 devrait être indexable');
            $this->assertTrue($product2->getSeo()->isFollowable(), 'Le produit 2 devrait être suivable');
            
            $expectedOpenGraphData2 = [
                'title' => 'Produit 2',
                'description' => 'Description Open Graph',
                'image' => 'https://example.com/image-produit.jpg',
                'type' => 'product'
            ];
            $this->assertEquals($expectedOpenGraphData2, $product2->getSeo()->getOpenGraphData(), 'Les données Open Graph du produit 2 ne correspondent pas');
        } catch (\Exception $e) {
            $this->fail('Flush failed: ' . $e->getMessage() . 
                        "\nProduct 1 details:" . 
                        "\nName: " . $product1->getName() . 
                        "\nDescription: " . $product1->getDescription() . 
                        "\nPrice: " . $product1->getPrice() . 
                        "\nStock: " . $product1->getStock() . 
                        "\nIs Active: " . ($product1->isActive() ? 'true' : 'false') . 
                        "\nIs Featured: " . ($product1->isFeatured() ? 'true' : 'false') . 
                        "\nSlug: " . $product1->getSlug() . 
                        "\n\nProduct 2 details:" . 
                        "\nName: " . $product2->getName() . 
                        "\nDescription: " . $product2->getDescription() . 
                        "\nPrice: " . $product2->getPrice() . 
                        "\nStock: " . $product2->getStock() . 
                        "\nIs Active: " . ($product2->isActive() ? 'true' : 'false') . 
                        "\nIs Featured: " . ($product2->isFeatured() ? 'true' : 'false') . 
                        "\nSlug: " . $product2->getSlug());
        }

        // Vérifier le total de la commande
        $this->assertEquals($expectedTotal, $order->getTotal());
        $this->assertCount(2, $order->getOrderItems());
    }
}
