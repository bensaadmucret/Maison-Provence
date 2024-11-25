<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
    ) {
    }

    #[Route('/checkout', name: 'app_checkout')]
    public function index(Request $request): Response
    {
        // Si l'utilisateur n'est pas connecté, redirection vers la page de connexion/inscription
        if (!$this->getUser()) {
            // Sauvegarder l'URL de retour en session
            $request->getSession()->set('checkout_redirect', $request->getUri());

            return $this->redirectToRoute('app_checkout_login');
        }

        // Vérifier si l'utilisateur a une adresse par défaut
        if (!$this->getUser()->getDefaultAddress()) {
            $this->addFlash('warning', 'Veuillez ajouter une adresse de livraison avant de continuer');

            return $this->redirectToRoute('app_address_new', [
                'redirect' => 'checkout',
            ]);
        }

        // Get the current cart
        $cart = $this->cartService->getCart();

        // Check if cart is empty
        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide');

            return $this->redirectToRoute('app_cart_show');
        }

        try {
            // Check if all items are still in stock
            foreach ($cart->getItems() as $item) {
                if ($item->getQuantity() > $item->getProduct()->getStock()) {
                    throw new \InvalidArgumentException(sprintf('Le produit "%s" n\'est plus disponible en quantité suffisante', $item->getProduct()->getName()));
                }
            }

            // Create a new order from the cart
            $order = $this->orderService->createOrderFromCart($cart);

            // Clear the cart after creating the order
            $this->cartService->clear();

            $this->addFlash('success', 'Votre commande a été créée avec succès');

            // Redirect to the order page
            return $this->redirectToRoute('app_order_show', [
                'reference' => $order->getReference(),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_cart_show');
        }
    }

    #[Route('/checkout/login', name: 'app_checkout_login')]
    public function login(): Response
    {
        // Si l'utilisateur est déjà connecté, redirection vers le checkout
        if ($this->getUser()) {
            return $this->redirectToRoute('app_checkout');
        }

        return $this->render('checkout/login.html.twig', [
            'cart' => $this->cartService->getCart(),
        ]);
    }
}
