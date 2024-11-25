<?php

namespace App\Tests\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartServiceTest extends TestCase
{
    private CartService $cartService;
    private RequestStack $requestStack;
    private EntityManagerInterface&MockObject $entityManager;
    private CartRepository&MockObject $cartRepository;
    private ProductRepository&MockObject $productRepository;
    private Security&MockObject $security;
    private SessionInterface&MockObject $session;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cartRepository = $this->createMock(CartRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->cartService = new CartService(
            $this->requestStack,
            $this->entityManager,
            $this->cartRepository,
            $this->productRepository,
            $this->security
        );
    }

    public function testGetCartForAnonymousUser(): void
    {
        $this->security->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $cart = $this->cartService->getCart();

        self::assertInstanceOf(Cart::class, $cart);
        self::assertNull($cart->getUser());
    }

    public function testGetCartForAuthenticatedUser(): void
    {
        $user = new User();
        $existingCart = new Cart();
        $existingCart->setUser($user);

        $this->security->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->cartRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $user])
            ->willReturn($existingCart);

        $cart = $this->cartService->getCart();

        self::assertSame($existingCart, $cart);
        self::assertSame($user, $cart->getUser());
    }

    public function testAddProduct(): void
    {
        $product = new Product();
        $product->setStock(10);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->cartService->addProduct(1);

        $cart = $this->cartService->getCart();
        self::assertTrue($cart->hasProduct($product));
        self::assertEquals(1, $cart->getQuantity($product));
    }

    public function testAddProductWithQuantity(): void
    {
        $product = new Product();
        $product->setStock(10);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->cartService->addProduct(1, 3);

        $cart = $this->cartService->getCart();
        self::assertTrue($cart->hasProduct($product));
        self::assertEquals(3, $cart->getQuantity($product));
    }

    public function testAddProductWithInsufficientStock(): void
    {
        $product = new Product();
        $product->setStock(2);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not enough stock');

        $this->cartService->addProduct(1, 3);
    }

    public function testRemoveProduct(): void
    {
        $product = new Product();
        $cart = $this->cartService->getCart();
        $cart->addProduct($product, 1);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->cartService->removeProduct(1);

        self::assertFalse($cart->hasProduct($product));
    }

    public function testUpdateQuantity(): void
    {
        $product = new Product();
        $product->setStock(10);
        $cart = $this->cartService->getCart();
        $cart->addProduct($product, 1);

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->cartService->updateQuantity(1, 3);

        self::assertEquals(3, $cart->getQuantity($product));
    }

    public function testGetTotal(): void
    {
        $product1 = new Product();
        $product1->setPrice(1000);
        $product2 = new Product();
        $product2->setPrice(2000);

        $cart = $this->cartService->getCart();
        $cart->addProduct($product1, 2);
        $cart->addProduct($product2, 1);

        self::assertEquals(4000, $this->cartService->getTotal());
    }

    public function testEmpty(): void
    {
        $product = new Product();
        $cart = $this->cartService->getCart();
        $cart->addProduct($product, 1);

        $this->cartService->empty();

        self::assertEmpty($cart->getProducts());
    }
}
