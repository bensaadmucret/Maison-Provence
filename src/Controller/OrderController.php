<?php

namespace App\Controller;

use App\DTO\OrderDTO;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $orderDTO = $this->serializer->deserialize($request->getContent(), OrderDTO::class, 'json');

        $errors = $this->validator->validate($orderDTO);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $order = $this->orderService->createOrder($orderDTO);

            return $this->json($order, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!$status) {
            return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $order = $this->orderService->updateOrderStatus($id, $status);

            return $this->json($order);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($id);

            return $this->json($order);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/user/{userId}', methods: ['GET'])]
    public function getUserOrders(int $userId): JsonResponse
    {
        try {
            $orders = $this->orderService->getUserOrders($userId);

            return $this->json($orders);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/status/{status}', methods: ['GET'])]
    public function getByStatus(string $status): JsonResponse
    {
        $orders = $this->orderService->getOrdersByStatus($status);

        return $this->json($orders);
    }

    #[Route('/date-range', methods: ['GET'])]
    public function getByDateRange(Request $request): JsonResponse
    {
        $start = new \DateTime($request->query->get('start'));
        $end = new \DateTime($request->query->get('end'));

        if (!$start || !$end) {
            return $this->json(['error' => 'Start and end dates are required'], Response::HTTP_BAD_REQUEST);
        }

        $orders = $this->orderService->getOrdersByDateRange($start, $end);

        return $this->json($orders);
    }
}
