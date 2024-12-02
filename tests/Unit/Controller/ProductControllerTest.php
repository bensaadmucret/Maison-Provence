<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ProductController;
use App\Entity\Product;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class ProductControllerTest extends TestCase
{
    private ProductController $controller;
    private ProductService&MockObject $productService;
    private Environment&MockObject $twig;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private SessionInterface $session;
    private EntityManagerInterface&MockObject $entityManager;
    private SerializerInterface&MockObject $serializer;
    private ValidatorInterface&MockObject $validator;
    private ContainerInterface&MockObject $container;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->twig = $this->createMock(Environment::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->session = new Session(new MockArraySessionStorage());
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        // Create and configure container mock
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')
            ->willReturnCallback(function ($id) {
                return match ($id) {
                    'twig', 'router', 'serializer' => true,
                    default => false,
                };
            });
        $this->container->method('get')
            ->willReturnCallback(function ($id) {
                return match ($id) {
                    'twig' => $this->twig,
                    'router' => $this->urlGenerator,
                    'serializer' => $this->serializer,
                    default => null,
                };
            });

        $this->controller = new ProductController(
            $this->productService,
            $this->entityManager,
            $this->serializer,
            $this->validator
        );
        $this->controller->setContainer($this->container);
    }

    public function testShow(): void
    {
        // Arrange
        $request = new Request();
        $request->setSession($this->session);

        $product = new Product();
        $product->setName('Test Product')
            ->setPrice(1000)
            ->setStock(10);

        $this->productService
            ->expects(self::once())
            ->method('getProductBySlug')
            ->with('test-product')
            ->willReturn($product);

        $this->productService
            ->expects(self::once())
            ->method('getSimilarProducts')
            ->with($product, 4)
            ->willReturn([]);

        $this->productService
            ->expects(self::once())
            ->method('getPreviousProduct')
            ->with($product)
            ->willReturn(null);

        $this->productService
            ->expects(self::once())
            ->method('getNextProduct')
            ->with($product)
            ->willReturn(null);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with(
                'product/show.html.twig',
                [
                    'product' => $product,
                    'similarProducts' => [],
                    'previousProduct' => null,
                    'nextProduct' => null,
                ]
            )
            ->willReturn('rendered template');

        // Act
        $response = $this->controller->show($request, 'test-product');

        // Assert
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('rendered template', $response->getContent());
    }

    public function testIndex(): void
    {
        // Arrange
        $products = [
            new Product(),
            new Product(),
        ];

        $this->productService
            ->expects(self::once())
            ->method('getActiveProducts')
            ->willReturn($products);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with(
                'product/index.html.twig',
                ['products' => $products]
            )
            ->willReturn('rendered template');

        // Act
        $response = $this->controller->index();

        // Assert
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('rendered template', $response->getContent());
    }

    public function testDelete(): void
    {
        // Arrange
        $request = new Request();
        $request->setSession($this->session);

        $this->productService
            ->expects(self::once())
            ->method('deleteProduct')
            ->with(1)
            ->willReturnSelf();

        // Act
        $response = $this->controller->delete($request, 1);

        // Assert
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertEquals('', $response->getContent());
    }

    public function testDeleteError(): void
    {
        // Arrange
        $request = new Request();
        $request->setSession($this->session);

        $errorMessage = 'Product could not be deleted';
        $this->productService
            ->expects(self::once())
            ->method('deleteProduct')
            ->with(1)
            ->willThrowException(new \Exception($errorMessage));

        // Configure serializer mock for JSON response
        $this->serializer
            ->expects(self::once())
            ->method('serialize')
            ->with(['error' => $errorMessage], 'json', ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS])
            ->willReturn(json_encode(['error' => $errorMessage]));

        // Act
        $response = $this->controller->delete($request, 1);

        // Assert
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotEmpty($response->getContent(), 'Response content should not be empty');
        self::assertJson($response->getContent(), 'Response content should be valid JSON');
        
        $content = json_decode($response->getContent(), true);
        self::assertNotNull($content, 'Response content should decode to a non-null value');
        self::assertIsArray($content, 'Response content should decode to an array');
        self::assertArrayHasKey('error', $content);
        self::assertEquals($errorMessage, $content['error']);
    }
}
