<?php

namespace App\Controller;

use App\Repository\LegalPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalController extends AbstractController
{
    public function __construct(
        private readonly LegalPageRepository $legalPageRepository
    ) {}

    #[Route('/mentions-legales', name: 'app_legal_mentions')]
    public function mentions(): Response
    {
        $legalPage = $this->legalPageRepository->findOneBy(['slug' => 'mentions-legales']);

        if (!$legalPage) {
            throw $this->createNotFoundException('La page des mentions légales n\'existe pas encore.');
        }

        return $this->render('legal/mentions.html.twig', [
            'page' => $legalPage
        ]);
    }
}
