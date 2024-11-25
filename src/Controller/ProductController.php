<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ProductService;
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
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $products = $this->productService->getActiveProducts();

        return $this->render('product/index.html.twig', [
            'products' => $products,
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
            ]);
        } catch (\Exception $e) {
            error_log('Erreur dans show() : '.$e->getMessage());
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
