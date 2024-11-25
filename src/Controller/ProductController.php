<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ProductService;
use App\Traits\SiteConfigurationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/products', name: 'app_product_')]
class ProductController extends AbstractController
{
    use SiteConfigurationTrait;

    public function __construct(
        private readonly ProductService $productService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $site = $this->getSiteConfiguration($this->entityManager);
        
        if (!$site->isEcommerceEnabled()) {
            $this->addFlash('warning', $site->getEcommerceDisabledMessage() ?? 'La boutique est temporairement désactivée.');
            return $this->redirectToRoute('app_home');
        }

        $products = $this->productService->getActiveProducts();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'site_configuration' => $site,
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(Request $request, string $slug): Response
    {
        $site = $this->getSiteConfiguration($this->entityManager);
        
        if (!$site->isEcommerceEnabled()) {
            $this->addFlash('warning', $site->getEcommerceDisabledMessage() ?? 'La boutique est temporairement désactivée.');
            return $this->redirectToRoute('app_home');
        }

        try {
            // Débogage détaillé de la requête
            error_log('=== Début de la méthode show ===');
            error_log('Slug reçu : ' . $slug);
            error_log('URL complète : ' . $request->getUri());
            error_log('Méthode HTTP : ' . $request->getMethod());
            error_log('Route matchée : ' . $request->attributes->get('_route'));
            error_log('Paramètres de route : ' . json_encode($request->attributes->get('_route_params')));
            
            // Vérifier la connexion à la base de données
            try {
                $conn = $this->entityManager->getConnection();
                $conn->connect();
                error_log('Connexion à la base de données OK');
            } catch (\Exception $e) {
                error_log('Erreur de connexion à la base de données : ' . $e->getMessage());
                throw $e;
            }
            
            // Vérifier tous les produits avant la recherche
            $allProducts = $this->entityManager->getRepository(Product::class)->findAll();
            error_log('Nombre total de produits en base : ' . count($allProducts));
            foreach ($allProducts as $p) {
                error_log(sprintf(
                    'Produit en base - ID=%d, Nom=%s, Slug=%s, Actif=%s',
                    $p->getId(),
                    $p->getName(),
                    $p->getSlug(),
                    $p->isActive() ? 'oui' : 'non'
                ));
            }
            
            // Vérifier les produits actifs
            $activeProducts = $this->productService->getActiveProducts();
            error_log('Produits actifs disponibles : ' . count($activeProducts));
            foreach ($activeProducts as $p) {
                error_log(sprintf(
                    'Produit actif - ID=%d, Nom=%s, Slug=%s',
                    $p->getId(),
                    $p->getName(),
                    $p->getSlug()
                ));
            }
            
            // Rechercher le produit spécifique
            $product = $this->productService->getProductBySlug($slug);
            if (!$product) {
                error_log('Produit non trouvé pour le slug : ' . $slug);
                
                // Vérifier si le produit existe mais est inactif
                $inactiveProduct = $this->entityManager->getRepository(Product::class)
                    ->findOneBy(['slug' => $slug]);
                if ($inactiveProduct) {
                    error_log(sprintf(
                        'Produit trouvé mais inactif - ID=%d, Nom=%s, Slug=%s, Actif=%s',
                        $inactiveProduct->getId(),
                        $inactiveProduct->getName(),
                        $inactiveProduct->getSlug(),
                        $inactiveProduct->isActive() ? 'oui' : 'non'
                    ));
                }
                
                throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
            }

            error_log('Produit trouvé : ' . json_encode([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'isActive' => $product->isActive()
            ]));

            // Récupérer les produits similaires
            $similarProducts = $this->productService->getSimilarProducts($product, 4);
            error_log('Nombre de produits similaires trouvés : ' . count($similarProducts));
            
            // Récupérer le produit précédent et suivant
            $previousProduct = $this->productService->getPreviousProduct($product);
            $nextProduct = $this->productService->getNextProduct($product);

            error_log('Produit précédent : ' . ($previousProduct ? $previousProduct->getSlug() : 'aucun'));
            error_log('Produit suivant : ' . ($nextProduct ? $nextProduct->getSlug() : 'aucun'));

            $site = $this->getSiteConfiguration($this->entityManager);

            error_log('=== Rendu du template ===');
            return $this->render('product/show.html.twig', [
                'product' => $product,
                'similarProducts' => $similarProducts,
                'previousProduct' => $previousProduct,
                'nextProduct' => $nextProduct,
                'site_configuration' => $site,
            ]);

        } catch (\Exception $e) {
            error_log('Erreur dans show() : ' . $e->getMessage());
            error_log($e->getTraceAsString());
            throw $e;
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            $data = $request->getContent();
            $productDTO = $this->serializer->deserialize($data, ProductDTO::class, 'json');

            $errors = $this->validator->validate($productDTO);
            if (count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $product = $this->productService->createProduct($productDTO);
            return $this->json(['id' => $product->getId()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            error_log('Erreur dans create() : ' . $e->getMessage());
            error_log($e->getTraceAsString());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): Response
    {
        try {
            $data = $request->getContent();
            $productDTO = $this->serializer->deserialize($data, ProductDTO::class, 'json');
            
            $errors = $this->validator->validate($productDTO);
            if (count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $product = $this->productService->updateProduct($id, $productDTO);
            return $this->json(['id' => $product->getId()]);
        } catch (\Exception $e) {
            error_log('Erreur dans update() : ' . $e->getMessage());
            error_log($e->getTraceAsString());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        try {
            $this->productService->deleteProduct($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            error_log('Erreur dans delete() : ' . $e->getMessage());
            error_log($e->getTraceAsString());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
