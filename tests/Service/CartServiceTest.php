<?php

namespace App\Tests\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\LoggingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartServiceTest extends TestCase
{
    private CartService $cartService;
    private EntityManagerInterface&MockObject $entityManager;
    private CartRepository&MockObject $cartRepository;
    private CartItemRepository&MockObject $cartItemRepository;
    private ProductRepository&MockObject $productRepository;
    private LoggingService&MockObject $loggingService;
    private RequestStack $requestStack;
    private SessionInterface&MockObject $session;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cartRepository = $this->createMock(CartRepository::class);
        $this->cartItemRepository = $this->createMock(CartItemRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->loggingService = $this->createMock(LoggingService::class);
        
        $this->requestStack = new RequestStack();
        $this->session = $this->createMock(SessionInterface::class);
        $this->session->method('getId')->willReturn('test_session_id');
        $this->requestStack->push($this->session);

        $this->cartService = new CartService(
            $this->entityManager,
            $this->cartRepository,
            $this->cartItemRepository,
            $this->productRepository,
            $this->requestStack,
            $this->loggingService
        );
    }

    public function testGetCartCreatesNewCartIfNotExists(): void
    {
        $this->session->expects(self::once())
            ->method('get')
            ->with('cart_id')
            ->willReturn(null);

        $this->cartRepository->expects(self::never())
            ->method('find');

        $cart = $this->cartService->getCart();

        self::assertInstanceOf(Cart::class, $cart);
        self::assertEquals('test_session_id', $cart->getSessionId());
    }

    public function testGetCartReturnsExistingCart(): void
    {
        $existingCart = new Cart();
        $existingCart->setSessionId('test_session_id');

        $this->session->expects(self::once())
            ->method('get')
            ->with('cart_id')
            ->willReturn(1);

        $this->cartRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($existingCart);

        $cart = $this->cartService->getCart();

        self::assertSame($existingCart, $cart);
    }

    public function testAddProductThrowsExceptionWhenProductNotFound(): void
    {
        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Produit non trouvé');

        $this->cartService->addProduct(1);
    }

    public function testAddProductThrowsExceptionWhenQuantityInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La quantité doit être supérieure à 0');

        $this->cartService->addProduct(1, 0);
    }

    public function testAddProductThrowsExceptionWhenInsufficientStock(): void
    {
        $product = new Product();
        $product->setStock(2);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock insuffisant');

        $this->cartService->addProduct(1, 3);
    }

    public function testAddProductCreatesNewCartItem(): void
    {
        $product = new Product();
        $product->setStock(5);
        $product->setPrice(1000);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->cartItemRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(CartItem::class));

        $this->cartService->addProduct(1, 2);
    }

    public function testUpdateQuantityThrowsExceptionWhenItemNotFound(): void
    {
        $this->cartItemRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Article non trouvé dans le panier');

        $this->cartService->updateQuantity(1, 2);
    }

    public function testRemoveItemThrowsExceptionWhenItemNotFound(): void
    {
        $this->cartItemRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Article non trouvé dans le panier');

        $this->cartService->removeItem(1);
    }

    public function testClearRemovesAllItems(): void
    {
        $cart = new Cart();
        $item1 = new CartItem();
        $item2 = new CartItem();
        $cart->addItem($item1);
        $cart->addItem($item2);

        $this->entityManager->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [self::identicalTo($item1)],
                [self::identicalTo($item2)]
            );

        $this->cartService->clear();
    }

    public function testGetTotalReturnsCorrectSum(): void
    {
        $cart = new Cart();
        $item1 = new CartItem();
        $item1->setQuantity(2);
        $item1->setPrice(1000);
        $item2 = new CartItem();
        $item2->setQuantity(1);
        $item2->setPrice(2000);
        
        $cart->addItem($item1);
        $cart->addItem($item2);

        $this->session->method('get')->willReturn(1);
        $this->cartRepository->method('find')->willReturn($cart);

        self::assertEquals(4000, $this->cartService->getTotal());
    }

    public function testGetItemCountReturnsCorrectCount(): void
    {
        $cart = new Cart();
        $item1 = new CartItem();
        $item1->setQuantity(2);
        $item2 = new CartItem();
        $item2->setQuantity(3);
        
        $cart->addItem($item1);
        $cart->addItem($item2);

        $this->session->method('get')->willReturn(1);
        $this->cartRepository->method('find')->willReturn($cart);

        self::assertEquals(5, $this->cartService->getItemCount());
    }

    public function testIsEmptyReturnsTrueForEmptyCart(): void
    {
        $cart = new Cart();
        
        $this->session->method('get')->willReturn(1);
        $this->cartRepository->method('find')->willReturn($cart);

        self::assertTrue($this->cartService->isEmpty());
    }
}
