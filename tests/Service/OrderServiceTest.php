<?php

namespace App\Tests\Service;

use App\DTO\OrderDTO;
use App\Entity\Address;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends TestCase
 */
class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private OrderRepository&MockObject $orderRepository;
    private ProductRepository&MockObject $productRepository;
    private UserRepository&MockObject $userRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private Security&MockObject $security;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->orderService = new OrderService(
            $this->entityManager,
            $this->orderRepository,
            $this->productRepository,
            $this->userRepository,
            $this->security
        );
    }

    public function testCreateOrder(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $product = new Product();
        $product->setName('Test Product')
            ->setPrice(2000)
            ->setStock(10);

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $address = new Address();
        $address->setStreet('123 Test St')
            ->setCity('Test City')
            ->setPostalCode('12345')
            ->setCountry('FR')
            ->setUser($user);

        $orderDTO = new OrderDTO();
        $orderDTO->setShippingAddressId($address->getId());
        $orderDTO->setBillingAddressId($address->getId());
        $orderDTO->setItems([
            ['productId' => 1, 'quantity' => 2],
        ]);

        $this->orderRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(function (Order $order) use ($user) {
                    return $order->getUser() === $user
                        && $order->getTotal() === 2000.0 * 2;
                }),
                true
            );

        // Act
        $order = $this->orderService->createOrder($orderDTO);

        // Assert
        self::assertInstanceOf(Order::class, $order);
        self::assertSame($user, $order->getUser());
        self::assertEquals(4000.0, $order->getTotal());
    }

    public function testCreateOrderWithInsufficientStock(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $product = new Product();
        $product->setName('Test Product')
            ->setPrice(2000)
            ->setStock(1);

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $address = new Address();
        $address->setStreet('123 Test St')
            ->setCity('Test City')
            ->setPostalCode('12345')
            ->setCountry('FR')
            ->setUser($user);

        $orderDTO = new OrderDTO();
        $orderDTO->setShippingAddressId($address->getId());
        $orderDTO->setBillingAddressId($address->getId());
        $orderDTO->setItems([
            ['productId' => 1, 'quantity' => 2],
        ]);

        $this->orderRepository
            ->expects(self::never())
            ->method('save');

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not enough stock for product Test Product');

        // Act
        $this->orderService->createOrder($orderDTO);
    }

    public function testGetOrder(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $order = new Order();
        $order->setUser($user);

        $this->orderRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Act
        $result = $this->orderService->getOrder(1);

        // Assert
        self::assertInstanceOf(Order::class, $result);
        self::assertSame($order, $result);
    }

    public function testGetOrderNotFound(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $this->security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->orderRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order not found');

        // Act
        $this->orderService->getOrder(1);
    }

    public function testGetOrderWrongUser(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $otherUser = new User();
        $otherUser->setEmail('other@example.com');

        $this->security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $order = new Order();
        $order->setUser($otherUser);

        $this->orderRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order does not belong to current user');

        // Act
        $this->orderService->getOrder(1);
    }
}
