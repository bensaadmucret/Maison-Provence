<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    #[Route('', name: 'app_cart_show')]
    public function show(): Response
    {
        return $this->render('cart/show.html.twig', [
            'cart' => $this->cartService->getCart(),
        ]);
    }

    #[Route('/add', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $productId = (int) $request->request->get('productId');
        $quantity = (int) $request->request->get('quantity', 1);

        try {
            $this->cartService->addProduct($productId, $quantity);
            $this->addFlash('success', 'Produit ajouté au panier avec succès');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/remove/{itemId}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $itemId): Response
    {
        $this->cartService->removeItem($itemId);
        $this->addFlash('success', 'Produit retiré du panier');

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/update/{itemId}', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, int $itemId): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);

        try {
            $this->cartService->updateQuantity($itemId, $quantity);
            $this->addFlash('success', 'Quantité mise à jour');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clear();
        $this->addFlash('success', 'Panier vidé');

        return $this->redirectToRoute('app_cart_show');
    }
}
