<?php

namespace App\Controller;

use App\Entity\PageSEO;
use App\Repository\TeamMemberRepository;
use App\Service\ProductService;
use App\Service\SEOService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SEOService $seoService,
        private TeamMemberRepository $teamMemberRepository,
        private ProductService $productService,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Récupérer ou créer la configuration SEO
        $seo = $this->seoService->getPageSEO('home');
        if (!$seo) {
            $seo = new PageSEO();
            $seo->setIdentifier('home');
            $seo->setMetaTitle('Accueil | Maison Provence');
            $seo->setMetaDescription('Découvrez Maison Provence, votre destination pour des produits authentiques de Provence.');
            $seo->setIndexable(true);
            $seo->setFollowable(true);
        }

        // Utiliser les services pour récupérer les données
        $featuredProducts = $this->productService->getFeaturedProducts();
        $teamMembers = $this->teamMemberRepository->findAllOrdered();
        $mediaUrl = $this->getParameter('media_url');
        $galleryImages = $this->productService->getGalleryImages($mediaUrl);

        return $this->render('home/index.html.twig', [
            'seo' => $seo,
            'featured_products' => $featuredProducts,
            'team_members' => $teamMembers,
            'galleryImages' => $galleryImages,
            'media_url' => $mediaUrl,
        ]);
    }
}
