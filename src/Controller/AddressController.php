<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account/addresses')]
#[IsGranted('ROLE_USER')]
class AddressController extends AbstractController
{
    #[Route('/', name: 'app_address_index', methods: ['GET'])]
    public function index(AddressRepository $addressRepository): Response
    {
        return $this->render('address/index.html.twig', [
            'addresses' => $addressRepository->findByUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_address_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $address = new Address();
        $address->setUser($this->getUser());

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si c'est la première adresse, la définir comme adresse par défaut
            if (0 === count($this->getUser()->getAddresses())) {
                $address->setIsDefault(true);
                $address->setIsBilling(true);
            }

            $entityManager->persist($address);
            $entityManager->flush();

            $this->addFlash('success', 'Votre adresse a été ajoutée avec succès.');

            // Rediriger vers le checkout si demandé
            if ('checkout' === $request->query->get('redirect')) {
                return $this->redirectToRoute('app_checkout');
            }

            return $this->redirectToRoute('app_address_index');
        }

        return $this->render('address/new.html.twig', [
            'address' => $address,
            'form' => $form,
            'redirect' => $request->query->get('redirect'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_address_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Address $address, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de l'adresse
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Votre adresse a été modifiée avec succès.');

            return $this->redirectToRoute('app_address_index');
        }

        return $this->render('address/edit.html.twig', [
            'address' => $address,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_address_delete', methods: ['POST'])]
    public function delete(Request $request, Address $address, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de l'adresse
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$address->getId(), $request->request->get('_token'))) {
            $entityManager->remove($address);
            $entityManager->flush();
            $this->addFlash('success', 'Votre adresse a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_address_index');
    }

    #[Route('/{id}/default', name: 'app_address_set_default', methods: ['POST'])]
    public function setDefault(Address $address, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de l'adresse
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Retirer le statut par défaut des autres adresses
        foreach ($this->getUser()->getAddresses() as $userAddress) {
            $userAddress->setIsDefault(false);
        }

        // Définir cette adresse comme adresse par défaut
        $address->setIsDefault(true);
        $entityManager->flush();

        $this->addFlash('success', 'Votre adresse par défaut a été mise à jour.');

        return $this->redirectToRoute('app_address_index');
    }

    #[Route('/{id}/billing', name: 'app_address_set_billing', methods: ['POST'])]
    public function setBilling(Address $address, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de l'adresse
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Retirer le statut de facturation des autres adresses
        foreach ($this->getUser()->getAddresses() as $userAddress) {
            $userAddress->setIsBilling(false);
        }

        // Définir cette adresse comme adresse de facturation
        $address->setIsBilling(true);
        $entityManager->flush();

        $this->addFlash('success', 'Votre adresse de facturation a été mise à jour.');

        return $this->redirectToRoute('app_address_index');
    }
}
