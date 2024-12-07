<?php

namespace App\Controller;

use App\Service\ProductService;
use App\Service\SiteConfigurationService;
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
    public function __construct(
        private readonly ProductService $productService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly SiteConfigurationService $siteConfigurationService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $sortBy = $request->query->get('sort', 'name');
        $order = $request->query->get('order', 'asc');
        $featured = $request->query->getBoolean('featured', false);
        $limit = 12;

        $products = $this->productService->getActiveProducts($page, $limit, $sortBy, $order, $featured);
        $totalProducts = $this->productService->getTotalActiveProducts($featured);
        $maxPages = ceil($totalProducts / $limit);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'currentPage' => $page,
            'maxPages' => $maxPages,
            'sortBy' => $sortBy,
            'order' => $order,
            'featured' => $featured,
            'media_url' => $this->getParameter('media_url'),
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(Request $request, string $slug): Response
    {
        try {
            $product = $this->productService->getProductBySlug($slug);
            if (!$product) {
                $this->addFlash('error', 'Le produit demandé n\'existe pas.');

                return $this->redirectToRoute('app_product_index');
            }

            $similarProducts = $this->productService->getSimilarProducts($product, 4);
            $previousProduct = $this->productService->getPreviousProduct($product);
            $nextProduct = $this->productService->getNextProduct($product);

            return $this->render('product/show.html.twig', [
                'product' => $product,
                'similarProducts' => $similarProducts,
                'previousProduct' => $previousProduct,
                'nextProduct' => $nextProduct,
                'media_url' => $this->getParameter('media_url'),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur dans show() : '.$e->getMessage());
            error_log($e->getTraceAsString());
            throw $e;
        }
    }

    #[Route('/search', name: 'search_products', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');

        if (empty($searchTerm)) {
            return $this->redirectToRoute('app_product_index');
        }

        $products = $this->productService->searchProducts($searchTerm);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'searchTerm' => $searchTerm,
            'media_url' => $this->getParameter('media_url'),
        ]);
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
            error_log('Erreur dans create() : '.$e->getMessage());
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
            error_log('Erreur dans update() : '.$e->getMessage());
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
            error_log('Erreur dans delete() : '.$e->getMessage());
            error_log($e->getTraceAsString());

            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
