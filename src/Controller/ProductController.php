<?php

namespace App\Controller;

use App\DTO\ProductDTO;
use App\Service\ProductService;
use App\Traits\SiteConfigurationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/products')]
class ProductController extends AbstractController
{
    use SiteConfigurationTrait;

    public function __construct(
        private readonly ProductService $productService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_product_index', methods: ['GET'])]
    public function index(): Response
    {
        $products = $this->productService->getActiveProducts();
        $site = $this->getSiteConfiguration($this->entityManager);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'site' => [
                'siteName' => $site->getSiteName(),
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        try {
            $product = $this->productService->getProduct($id);
            $site = $this->getSiteConfiguration($this->entityManager);

            return $this->render('product/show.html.twig', [
                'product' => $product,
                'site' => [
                    'siteName' => $site->getSiteName(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Le produit demandé n\'existe pas.');
            return $this->redirectToRoute('app_product_index');
        }
    }

    #[Route('', name: 'app_product_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->getContent();
        $productDTO = $this->serializer->deserialize($data, ProductDTO::class, 'json');

        $errors = $this->validator->validate($productDTO);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $product = $this->productService->createProduct($productDTO);
            return $this->json(['id' => $product->getId()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'app_product_update', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $data = $request->getContent();
        $productDTO = $this->serializer->deserialize($data, ProductDTO::class, 'json');

        $errors = $this->validator->validate($productDTO);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->productService->updateProduct($id, $productDTO);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $this->productService->deleteProduct($id);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
