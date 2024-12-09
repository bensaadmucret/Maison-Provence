<?php

namespace App\Tests\Entity;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\CartItem;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CartTest extends KernelTestCase
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
        $connection->executeStatement('TRUNCATE TABLE cart_item');
        $connection->executeStatement('TRUNCATE TABLE cart');
        $connection->executeStatement('TRUNCATE TABLE `user`');
        $connection->executeStatement('TRUNCATE TABLE product');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->entityManager->clear();
        parent::tearDown();
    }

    public function testCreateCart()
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('cart_test_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setFirstName('John');
        $user->setLastName('Doe');

        // Créer un panier
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setSessionId('test_session_id');

        // Persister les entités
        $this->entityManager->persist($user);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        // Vérifier que le panier a été créé
        $cartRepository = $this->entityManager->getRepository(Cart::class);
        $foundCart = $cartRepository->findOneBy(['user' => $user]);

        $this->assertNotNull($foundCart);
        $this->assertEquals($user, $foundCart->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $foundCart->getCreatedAt());
        $this->assertEquals('test_session_id', $foundCart->getSessionId());
    }

    public function testAddCartItem()
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('cart_item_test_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setFirstName('Jane');
        $user->setLastName('Smith');

        // Créer un produit
        $product = new Product();
        $product->setName('Produit de Test');
        $product->setDescription('Description du produit de test');
        $product->setPrice(49.99);
        $product->setStock(10);
        $product->setSlug('produit-de-test-' . uniqid());
        $product->setCategory(null);  // Explicitement définir la catégorie comme null

        // Créer un panier
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setSessionId('test_session_id_2');

        // Créer un article de panier
        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product);
        $cartItem->setQuantity(2);

        // Ajouter l'article au panier
        $cart->addItem($cartItem);

        // Persister les entités
        $this->entityManager->persist($user);
        $this->entityManager->persist($product);
        $this->entityManager->persist($cart);
        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();

        // Vérifier que l'article a été ajouté
        $this->assertCount(1, $cart->getItems());
        $this->assertEquals($cartItem, $cart->getItems()[0]);
        $this->assertEquals(2, $cartItem->getQuantity());
        $this->assertEquals(49.99, $cartItem->getPrice());
        $this->assertEquals($product, $cartItem->getProduct());
    }
}
