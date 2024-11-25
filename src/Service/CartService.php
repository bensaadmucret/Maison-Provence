<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private ?Cart $cart = null;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository,
        private ProductRepository $productRepository,
        private Security $security
    ) {
    }

    public function getCart(): Cart
    {
        if ($this->cart === null) {
            /** @var User|null $user */
            $user = $this->security->getUser();

            if ($user) {
                $this->cart = $this->cartRepository->findOneBy(['user' => $user]) ?? new Cart();
                $this->cart->setUser($user);
            } else {
                $this->cart = new Cart();
            }

            if (!$this->cart->getId()) {
                $this->entityManager->persist($this->cart);
                $this->entityManager->flush();
            }
        }

        return $this->cart;
    }

    public function addProduct(int $productId, int $quantity = 1): void
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        if ($product->getStock() < $quantity) {
            throw new \InvalidArgumentException('Not enough stock');
        }

        $cart = $this->getCart();
        $cart->addProduct($product, $quantity);

        $this->entityManager->flush();
    }

    public function removeProduct(int $productId): void
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $cart = $this->getCart();
        $cart->removeProduct($product);

        $this->entityManager->flush();
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        if ($product->getStock() < $quantity) {
            throw new \InvalidArgumentException('Not enough stock');
        }

        $cart = $this->getCart();
        $cart->setQuantity($product, $quantity);

        $this->entityManager->flush();
    }

    public function getTotal(): float
    {
        $total = 0.0;
        $cart = $this->getCart();

        foreach ($cart->getProducts() as $product) {
            $total += $product->getPrice() * $cart->getQuantity($product);
        }

        return $total;
    }

    public function empty(): void
    {
        $cart = $this->getCart();
        $cart->empty();

        $this->entityManager->flush();
    }
}
