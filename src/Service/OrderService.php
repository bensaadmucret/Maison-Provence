<?php

namespace App\Service;

use App\DTO\OrderDTO;
use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

class OrderService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    public function createOrder(OrderDTO $orderDTO): ?Order
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $order = new Order();
        $order->setUser($user);
        $order->setReference(Uuid::v4()->toRfc4122());
        $order->setStatus(self::STATUS_PENDING);

        // Set default addresses from user if available
        if ($user->getDefaultAddress()) {
            $order->setShippingAddress($user->getDefaultAddress());
        }
        if ($user->getDefaultBillingAddress()) {
            $order->setBillingAddress($user->getDefaultBillingAddress());
        }

        $order->setPaymentMethod($orderDTO->getPaymentMethod() ?? 'stripe');

        $totalAmount = 0.0;
        foreach ($orderDTO->getItems() as $itemDTO) {
            $product = $this->productRepository->find($itemDTO->getProductId());
            if (!$product) {
                continue;
            }

            $quantity = max(1, $itemDTO->getQuantity() ?? 1);
            if (null !== $product->getStock() && $product->getStock() < $quantity) {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity((int) $quantity);
            $orderItem->setPrice((float) $product->getPrice());
            $orderItem->setOrderRef($order);

            $totalAmount += $orderItem->getPrice() * $orderItem->getQuantity();
            $order->addOrderItem($orderItem);

            // Update product stock
            if (null !== $product->getStock()) {
                $newStock = $product->getStock() - $quantity;
                if ($newStock >= 0) {
                    $product->setStock($newStock);
                    $this->entityManager->persist($product);
                }
            }
        }

        $order->setTotal($totalAmount);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * @return array<Order>
     */
    public function getUserOrders(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function getOrder(int $id): ?Order
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $order = $this->orderRepository->find($id);
        if (!$order || $order->getUser() !== $user) {
            return null;
        }

        return $order;
    }

    public function updateOrderStatus(int $id, string $status): ?Order
    {
        $order = $this->getOrder($id);
        if (!$order) {
            return null;
        }

        $order->setStatus($status);
        $this->entityManager->flush();

        return $order;
    }

    public function createOrderFromCart(Cart $cart): ?Order
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $order = new Order();
        $order->setUser($user);
        $order->setReference(Uuid::v4()->toRfc4122());
        $order->setStatus(self::STATUS_PENDING);
        $order->setPaymentMethod('stripe');

        if ($user->getDefaultAddress()) {
            $order->setShippingAddress($user->getDefaultAddress());
        }

        if ($user->getDefaultBillingAddress()) {
            $order->setBillingAddress($user->getDefaultBillingAddress());
        }

        $totalAmount = 0.0;
        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            if (!$product) {
                continue;
            }

            $quantity = $cartItem->getQuantity();
            if (null !== $product->getStock() && $product->getStock() < $quantity) {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity((int) $quantity);
            $orderItem->setPrice((float) $product->getPrice());
            $orderItem->setOrderRef($order);

            $totalAmount += $orderItem->getPrice() * $orderItem->getQuantity();
            $order->addOrderItem($orderItem);

            // Update product stock
            if (null !== $product->getStock()) {
                $newStock = $product->getStock() - $quantity;
                if ($newStock >= 0) {
                    $product->setStock($newStock);
                    $this->entityManager->persist($product);
                }
            }
        }

        $order->setTotal($totalAmount);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
