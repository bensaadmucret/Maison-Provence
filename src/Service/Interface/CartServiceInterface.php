<?php

namespace App\Service\Interface;

use App\Entity\Cart;

interface CartServiceInterface
{
    /**
     * Get the current cart from session.
     */
    public function getCart(): Cart;

    /**
     * Add a product to the cart.
     *
     * @param int $productId Product ID to add
     * @param int $quantity  Quantity to add (default: 1)
     *
     * @throws \InvalidArgumentException If product not found or not enough stock
     */
    public function addProduct(int $productId, int $quantity = 1): void;

    /**
     * Remove an item from the cart.
     *
     * @param int $itemId CartItem ID to remove
     *
     * @throws \InvalidArgumentException If item not found
     */
    public function removeItem(int $itemId): void;

    /**
     * Update the quantity of a cart item.
     *
     * @param int $itemId   CartItem ID to update
     * @param int $quantity New quantity
     *
     * @throws \InvalidArgumentException If item not found or quantity invalid
     */
    public function updateQuantity(int $itemId, int $quantity): void;

    /**
     * Clear all items from the cart.
     */
    public function clear(): void;

    /**
     * Get the total price of the cart.
     */
    public function getTotal(): float;

    /**
     * Get the total number of items in the cart.
     */
    public function getItemCount(): int;

    /**
     * Check if the cart is empty.
     */
    public function isEmpty(): bool;
}
