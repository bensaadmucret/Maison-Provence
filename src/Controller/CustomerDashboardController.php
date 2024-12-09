<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\InvoiceRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer')]
#[IsGranted('ROLE_USER')]
final class CustomerDashboardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/dashboard', name: 'app_customer_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
    ): Response {
        $user = $this->getUser();

        $allOrders = $orderRepository->findBy(['user' => $user]);

        $ordersCount = count($allOrders);
        $pendingOrdersCount = count(array_filter($allOrders, fn ($order) => 'pending' === $order->getStatus()));
        $totalSpent = array_reduce($allOrders, fn ($sum, $order) => $sum + $order->getTotalAmount(), 0);

        $invoicesCount = $invoiceRepository->count(['user' => $user]);

        $recentOrders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('customer/dashboard/index.html.twig', [
            'recent_orders' => $recentOrders,
            'orders_count' => $ordersCount,
            'pending_orders_count' => $pendingOrdersCount,
            'total_spent' => $totalSpent,
            'invoices_count' => $invoicesCount,
        ]);
    }

    #[Route('/profile', name: 'app_customer_profile')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        // Formulaire du profil
        $profileForm = $this->createForm(ProfileType::class, $user, [
            'method' => 'POST',
            'action' => $this->generateUrl('app_customer_profile'),
        ]);

        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted()) {
            try {
                if (!$profileForm->isValid()) {
                    foreach ($profileForm->getErrors(true) as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }

                    return $this->redirectToRoute('app_customer_profile');
                }

                $this->entityManager->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès');
            } catch (InvalidCsrfTokenException $e) {
                $this->addFlash('error', 'Token CSRF invalide. Veuillez réessayer.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du profil');
            }

            return $this->redirectToRoute('app_customer_profile');
        }

        // Formulaire de changement de mot de passe
        $passwordForm = $this->createForm(ChangePasswordType::class, null, [
            'method' => 'POST',
            'action' => $this->generateUrl('app_customer_profile'),
        ]);

        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted()) {
            try {
                if (!$passwordForm->isValid()) {
                    foreach ($passwordForm->getErrors(true) as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }

                    return $this->redirectToRoute('app_customer_profile');
                }

                $data = $passwordForm->getData();

                if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect');

                    return $this->redirectToRoute('app_customer_profile');
                }

                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $data['newPassword'])
                );

                $this->entityManager->flush();
                $this->addFlash('success', 'Mot de passe modifié avec succès');
            } catch (InvalidCsrfTokenException $e) {
                $this->addFlash('error', 'Token CSRF invalide. Veuillez réessayer.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du changement de mot de passe');
            }

            return $this->redirectToRoute('app_customer_profile');
        }

        return $this->render('customer/dashboard/profile.html.twig', [
            'form' => $profileForm->createView(),
            'password_form' => $passwordForm->createView(),
        ]);
    }

    #[Route('/orders', name: 'app_customer_orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('customer/dashboard/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders/{reference}', name: 'app_customer_order_detail')]
    public function orderDetail(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneBy([
            'reference' => $reference,
            'user' => $this->getUser(),
        ]);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        return $this->render('customer/dashboard/order_detail.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/invoices', name: 'app_customer_invoices')]
    public function invoices(InvoiceRepository $invoiceRepository): Response
    {
        $invoices = $invoiceRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('customer/dashboard/invoices.html.twig', [
            'invoices' => $invoices,
        ]);
    }

    #[Route('/addresses', name: 'app_customer_addresses')]
    public function addresses(): Response
    {
        return $this->render('customer/dashboard/addresses.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
