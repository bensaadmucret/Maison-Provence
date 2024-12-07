<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('cart/show.html.twig', [
            'cart' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal(),
            'itemCount' => $this->cartService->getItemCount(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $productId = (int) $request->request->get('productId');
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        try {
            $this->cartService->addProduct($productId, $quantity);
            $this->addFlash('success', 'Produit ajouté au panier avec succès');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Produit ajouté au panier',
                    'cartTotal' => $this->cartService->getTotal(),
                    'cartCount' => $this->cartService->getItemCount(),
                ]);
            }
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/remove/{itemId}', name: 'remove', methods: ['POST', 'DELETE'])]
    public function remove(int $itemId, Request $request): Response
    {
        try {
            $this->cartService->removeItem($itemId);
            $this->addFlash('success', 'Produit retiré du panier');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Produit retiré du panier',
                    'cartTotal' => $this->cartService->getTotal(),
                    'cartCount' => $this->cartService->getItemCount(),
                ]);
            }
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/update/{itemId}', name: 'update', methods: ['POST', 'PATCH'])]
    public function update(Request $request, int $itemId): Response
    {
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        try {
            $this->cartService->updateQuantity($itemId, $quantity);
            $this->addFlash('success', 'Quantité mise à jour');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Quantité mise à jour',
                    'cartTotal' => $this->cartService->getTotal(),
                    'cartCount' => $this->cartService->getItemCount(),
                ]);
            }
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/clear', name: 'clear', methods: ['POST', 'DELETE'])]
    public function clear(Request $request): Response
    {
        $this->cartService->clear();
        $this->addFlash('success', 'Panier vidé');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Panier vidé',
                'cartTotal' => 0,
                'cartCount' => 0,
            ]);
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/count', name: 'count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        return new JsonResponse([
            'count' => $this->cartService->getItemCount(),
            'total' => $this->cartService->getTotal(),
        ]);
    }
}
