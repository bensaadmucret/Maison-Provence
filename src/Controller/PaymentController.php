<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/payment/{reference}', name: 'app_payment')]
    public function payment(Order $order): Response
    {
        if ('pending' !== $order->getStatus()) {
            $this->addFlash('error', 'Cette commande ne peut pas être payée.');

            return $this->redirectToRoute('app_order_history');
        }

        $paymentIntent = $this->stripeService->createPaymentIntent($order);

        return $this->render('payment/index.html.twig', [
            'clientSecret' => $paymentIntent->client_secret,
            'publicKey' => $this->stripeService->getPublicKey(),
            'order' => $order,
        ]);
    }

    #[Route('/payment/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = $this->stripeService->handleWebhook($payload, $sigHeader);

            if ('payment_intent.succeeded' === $event['type']) {
                $orderId = $event['data']['object']['metadata']['order_id'];
                $order = $this->entityManager->getRepository(Order::class)->find($orderId);

                if ($order) {
                    $order->setStatus('paid');
                    $order->setPaymentId($event['data']['object']['id']);
                    $this->entityManager->flush();
                }
            }

            return new JsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function success(): Response
    {
        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
