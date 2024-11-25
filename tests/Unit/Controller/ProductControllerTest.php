<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ProductController;
use App\Entity\Product;
use App\Entity\SiteConfiguration;
use App\Service\ProductService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ProductControllerTest extends TestCase
{
    private ProductController $controller;
    private ProductService $productService;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private Environment $twig;
    private SiteConfiguration $siteConfig;
    private RequestStack $requestStack;
    private Session $session;
    private UrlGeneratorInterface $router;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(Session::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->requestStack->method('getSession')
            ->willReturn($this->session);

        $this->session->method('getFlashBag')
            ->willReturn(new FlashBag());

        $siteConfigRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($siteConfigRepository) {
                if ($entityClass === SiteConfiguration::class) {
                    return $siteConfigRepository;
                }
                return $this->createMock(EntityRepository::class);
            });

        $siteConfig = new SiteConfiguration();
        $siteConfig->setSiteName('Maison Provence');
        $siteConfigRepository->method('findOneBy')
            ->willReturn($siteConfig);

        $product = new Product();
        $product->setName('Test Product');
        $product->setSlug('test-product');
        $product->setIsActive(true);

        $this->productService->method('getProductBySlug')
            ->willReturnCallback(function ($slug) use ($product) {
                if ($slug === 'test-product') {
                    return $product;
                }
                if ($slug === 'non-existent') {
                    return null;
                }
                return null;
            });

        $this->controller = new ProductController(
            $this->productService,
            $this->entityManager,
            $this->serializer,
            $this->validator
        );

        $container = $this->getContainer();
        $this->controller->setContainer($container);
    }

    private function getContainer(): object
    {
        $container = $this->createMock('Psr\Container\ContainerInterface');
        $container->method('get')
            ->willReturnCallback(function ($id) {
                if ($id === 'twig') {
                    return $this->twig;
                }
                if ($id === 'request_stack') {
                    return $this->requestStack;
                }
                if ($id === 'router') {
                    return $this->router;
                }
                if ($id === 'twig.loader.native_filesystem') {
                    return $this->createMock(FilesystemLoader::class);
                }
                return null;
            });
        $container->method('has')
            ->willReturnCallback(function ($id) {
                return in_array($id, ['twig', 'request_stack', 'router', 'twig.loader.native_filesystem']);
            });
        return $container;
    }

    public function testIndex(): void
    {
        $products = [
            $this->createMock(Product::class),
            $this->createMock(Product::class),
        ];

        $this->productService->expects($this->once())
            ->method('getActiveProducts')
            ->willReturn($products);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'product/index.html.twig',
                $this->callback(function ($params) use ($products) {
                    return $params['products'] === $products &&
                           isset($params['site_configuration']) &&
                           $params['site_configuration']->getSiteName() === 'Maison Provence';
                })
            )
            ->willReturn('rendered template');

        $response = $this->controller->index();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShow(): void
    {
        $request = Request::create('/products/test-product', 'GET');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'product/show.html.twig',
                $this->callback(function ($params) {
                    return $params['product']->getName() === 'Test Product' &&
                           isset($params['site_configuration']) &&
                           $params['site_configuration']->getSiteName() === 'Maison Provence';
                })
            )
            ->willReturn('rendered template');

        $response = $this->controller->show($request, 'test-product');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShowNotFound(): void
    {
        $request = Request::create('/products/non-existent', 'GET');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('app_product_index')
            ->willReturn('/products');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn(new FlashBag());

        $response = $this->controller->show($request, 'non-existent');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/products', $response->headers->get('Location'));
    }
}
