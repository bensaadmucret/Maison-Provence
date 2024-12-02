<?php

namespace App\Tests\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CartControllerTest extends WebTestCase
{
    private $client;
    private $cartService;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();
        $this->cartService = $this->createMock(CartService::class);
        $container->set('App\Service\CartService', $this->cartService);
    }

    public function testShowCart(): void
    {
        $cart = new Cart();
        $this->cartService->expects($this->once())
            ->method('getCart')
            ->willReturn($cart);
        
        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(4000.0);
            
        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(3);

        $this->client->request('GET', '/cart');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Votre panier');
    }

    public function testAddProductSuccess(): void
    {
        $this->cartService->expects($this->once())
            ->method('addProduct')
            ->with(1, 2)
            ->willReturn(null);

        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(2000.0);

        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(2);

        $this->client->request(
            'POST',
            '/cart/add',
            ['productId' => 1, 'quantity' => 2],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(2000.0, $responseData['cartTotal']);
        $this->assertEquals(2, $responseData['cartCount']);
    }

    public function testAddProductWithInvalidStock(): void
    {
        $this->cartService->expects($this->once())
            ->method('addProduct')
            ->with(1, 10)
            ->willThrowException(new \InvalidArgumentException('Stock insuffisant'));

        $this->client->request(
            'POST',
            '/cart/add',
            ['productId' => 1, 'quantity' => 10],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Stock insuffisant', $responseData['message']);
    }

    public function testRemoveItemSuccess(): void
    {
        $this->cartService->expects($this->once())
            ->method('removeItem')
            ->with(1)
            ->willReturn(null);

        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(1000.0);

        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(1);

        $this->client->request(
            'POST',
            '/cart/remove/1',
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(1000.0, $responseData['cartTotal']);
        $this->assertEquals(1, $responseData['cartCount']);
    }

    public function testUpdateQuantitySuccess(): void
    {
        $this->cartService->expects($this->once())
            ->method('updateQuantity')
            ->with(1, 3)
            ->willReturn(null);

        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(3000.0);

        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(3);

        $this->client->request(
            'POST',
            '/cart/update/1',
            ['quantity' => 3],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(3000.0, $responseData['cartTotal']);
        $this->assertEquals(3, $responseData['cartCount']);
    }

    public function testUpdateQuantityWithInvalidQuantity(): void
    {
        $this->cartService->expects($this->once())
            ->method('updateQuantity')
            ->with(1, 0)
            ->willThrowException(new \InvalidArgumentException('La quantité doit être supérieure à 0'));

        $this->client->request(
            'POST',
            '/cart/update/1',
            ['quantity' => 0],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('La quantité doit être supérieure à 0', $responseData['message']);
    }

    public function testClearCart(): void
    {
        $this->cartService->expects($this->once())
            ->method('clear');

        $this->client->request(
            'POST',
            '/cart/clear',
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(0, $responseData['cartTotal']);
        $this->assertEquals(0, $responseData['cartCount']);
    }

    public function testGetCartCount(): void
    {
        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(5);

        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(5000.0);

        $this->client->request('GET', '/cart/count');

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(5, $responseData['count']);
        $this->assertEquals(5000.0, $responseData['total']);
    }

    public function testUpdateQuantityWithNegativeQuantity(): void
    {
        $this->cartService->expects($this->once())
            ->method('updateQuantity')
            ->with(1, 1)  // The controller should convert negative to 1
            ->willReturn(null);

        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(1000.0);

        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(1);

        $this->client->request(
            'POST',
            '/cart/update/1',
            ['quantity' => -5],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(1000.0, $responseData['cartTotal']);
        $this->assertEquals(1, $responseData['cartCount']);
    }

    public function testUpdateQuantityWithoutAjax(): void
    {
        $this->cartService->expects($this->once())
            ->method('updateQuantity')
            ->with(1, 3)
            ->willReturn(null);

        $this->client->request(
            'POST',
            '/cart/update/1',
            ['quantity' => 3]
        );

        $this->assertResponseRedirects('/cart');
    }

    public function testAddProductWithoutQuantity(): void
    {
        $this->cartService->expects($this->once())
            ->method('addProduct')
            ->with(1, 1)  // Should default to 1
            ->willReturn(null);

        $this->cartService->expects($this->once())
            ->method('getTotal')
            ->willReturn(1000.0);

        $this->cartService->expects($this->once())
            ->method('getItemCount')
            ->willReturn(1);

        $this->client->request(
            'POST',
            '/cart/add',
            ['productId' => 1],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals(1000.0, $responseData['cartTotal']);
        $this->assertEquals(1, $responseData['cartCount']);
    }
}
