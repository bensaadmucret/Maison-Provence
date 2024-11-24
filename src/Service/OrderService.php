<?php

namespace App\Service;

use App\DTO\OrderDTO;
use App\DTO\OrderItemDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsService]
#[AutoconfigureTag('app.service')]
class OrderService
{
    public function __construct(
        #[Autowire(service: OrderRepository::class)]
        private readonly OrderRepository $orderRepository,
        #[Autowire(service: ProductRepository::class)]
        private readonly ProductRepository $productRepository,
        #[Autowire(service: UserRepository::class)]
        private readonly UserRepository $userRepository,
        #[Autowire(service: EntityManagerInterface::class)]
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createOrder(OrderDTO $orderDTO): Order
    {
        $user = $this->userRepository->find($orderDTO->getUserId());
        if (!$user) {
            throw new EntityNotFoundException('User not found');
        }

        $order = new Order();
        $order
            ->setReference(Uuid::v4()->toRfc4122())
            ->setUser($user)
            ->setShippingAddress($orderDTO->getShippingAddress())
            ->setShippingMethod($orderDTO->getShippingMethod())
            ->setPaymentMethod($orderDTO->getPaymentMethod())
            ->setStatus('pending');

        $total = 0;
        foreach ($orderDTO->getItems() as $itemDTO) {
            $orderItem = $this->createOrderItem($itemDTO);
            $order->addOrderItem($orderItem);
            $total += $orderItem->getPrice() * $orderItem->getQuantity();
        }

        $order->setTotal($total);

        $this->orderRepository->save($order, true);

        return $order;
    }

    private function createOrderItem(OrderItemDTO $itemDTO): OrderItem
    {
        $product = $this->productRepository->find($itemDTO->getProductId());
        if (!$product) {
            throw new EntityNotFoundException('Product not found');
        }

        if ($product->getStock() < $itemDTO->getQuantity()) {
            throw new \InvalidArgumentException('Not enough stock available');
        }

        $orderItem = new OrderItem();
        $orderItem
            ->setProduct($product)
            ->setQuantity($itemDTO->getQuantity())
            ->setPrice($product->getPrice());

        // Update product stock
        $product->setStock($product->getStock() - $itemDTO->getQuantity());
        $this->entityManager->persist($product);

        return $orderItem;
    }

    public function updateOrderStatus(int $id, string $status): Order
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            throw new EntityNotFoundException('Order not found');
        }

        $order->setStatus($status);
        $this->orderRepository->save($order, true);

        return $order;
    }

    public function getOrder(int $id): Order
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            throw new EntityNotFoundException('Order not found');
        }

        return $order;
    }

    public function getUserOrders(int $userId): array
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new EntityNotFoundException('User not found');
        }

        return $this->orderRepository->findByUser($user);
    }

    public function getOrdersByStatus(string $status): array
    {
        return $this->orderRepository->findByStatus($status);
    }

    public function getOrdersByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->orderRepository->findByDateRange($start, $end);
    }
}
