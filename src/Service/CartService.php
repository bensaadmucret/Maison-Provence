<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Service\Interface\CartServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService implements CartServiceInterface
{
    private ?Cart $cart = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
        private readonly ProductRepository $productRepository,
        private readonly RequestStack $requestStack,
        private readonly LoggingService $loggingService,
    ) {
    }

    public function getCart(): Cart
    {
        if (null === $this->cart) {
            $session = $this->requestStack->getSession();
            $cartId = $session->get('cart_id');

            if ($cartId) {
                $this->cart = $this->cartRepository->find($cartId);
            }

            if (!$this->cart) {
                $this->cart = new Cart();
                $this->cart->setSessionId($session->getId());
                $this->entityManager->persist($this->cart);
                $this->entityManager->flush();
                $this->loggingService->logCartOperation('update', $this->cart);
                $session->set('cart_id', $this->cart->getId());
            }
        }

        return $this->cart;
    }

    public function addProduct(int $productId, int $quantity = 1): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à 0');
        }

        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Produit non trouvé');
        }

        if ($product->getStock() < $quantity) {
            throw new \InvalidArgumentException('Stock insuffisant');
        }

        $cart = $this->getCart();
        $cartItem = $this->cartItemRepository->findOneBy([
            'cart' => $cart,
            'product' => $product,
        ]);

        if ($cartItem) {
            $newQuantity = $cartItem->getQuantity() + $quantity;
            if ($product->getStock() < $newQuantity) {
                throw new \InvalidArgumentException('Stock insuffisant pour la quantité totale');
            }
            $cartItem->setQuantity($newQuantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice($product->getPrice());
            $this->entityManager->persist($cartItem);
        }

        $this->entityManager->flush();
        $this->loggingService->logCartOperation('update', $cartItem->getCart());
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à 0');
        }

        $cartItem = $this->cartItemRepository->find($itemId);
        if (!$cartItem) {
            throw new \InvalidArgumentException('Article non trouvé dans le panier');
        }

        $product = $cartItem->getProduct();
        if ($product->getStock() < $quantity) {
            throw new \InvalidArgumentException('Stock insuffisant');
        }

        $cartItem->setQuantity($quantity);
        $this->entityManager->flush();
        $this->loggingService->logCartOperation('update', $cartItem->getCart());
    }

    public function removeItem(int $itemId): void
    {
        $cartItem = $this->cartItemRepository->find($itemId);
        if (!$cartItem) {
            throw new \InvalidArgumentException('Article non trouvé dans le panier');
        }

        $cart = $cartItem->getCart();
        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();
        $this->loggingService->logCartOperation('update', $cart);
    }

    public function clear(): void
    {
        $cart = $this->getCart();
        foreach ($cart->getItems() as $item) {
            $this->entityManager->remove($item);
        }
        $this->entityManager->flush();
        $this->loggingService->logCartCleared($cart);
    }

    public function getTotal(): float
    {
        return $this->getCart()->getTotal();
    }

    public function getItemCount(): int
    {
        return $this->getCart()->getItemsCount();
    }

    public function isEmpty(): bool
    {
        return $this->getCart()->isEmpty();
    }
}
