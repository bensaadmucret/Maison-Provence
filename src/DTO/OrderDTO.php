<?php

namespace App\DTO;

class OrderDTO
{
    private ?string $shippingAddress = null;
    private ?string $shippingMethod = null;
    private ?string $paymentMethod = null;
    private array $items = [];
    private ?int $userId = null;

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(string $shippingMethod): self
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return array<OrderItemDTO>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<OrderItemDTO> $items
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function addItem(OrderItemDTO $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
