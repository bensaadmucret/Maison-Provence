<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ProductController;
use App\Entity\Product;
use App\Entity\SiteConfiguration;
use App\Service\ProductService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

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
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        
        $this->siteConfig = new SiteConfiguration();
        $this->siteConfig->setSiteName('Maison Provence');

        $configRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        $configRepository->method('findOneBy')->willReturn($this->siteConfig);
        
        $this->entityManager->method('getRepository')
            ->with(SiteConfiguration::class)
            ->willReturn($configRepository);

        // Set up session
        $this->session = new Session(new MockArraySessionStorage());
        $this->session->start();
        
        $this->requestStack = new RequestStack();
        $request = new Request();
        $request->setSession($this->session);
        $this->requestStack->push($request);

        $this->router->method('generate')
            ->willReturnCallback(function ($route) {
                return '/' . $route;
            });

        $this->controller = new ProductController(
            $this->productService,
            $this->serializer,
            $this->validator,
            $this->entityManager
        );
        $this->controller->setContainer($this->getContainer());
    }

    private function getContainer(): object
    {
        $container = $this->createMock('Psr\Container\ContainerInterface');
        $container->method('has')->willReturn(true);
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
                return null;
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
                           isset($params['site']) &&
                           $params['site']['siteName'] === 'Maison Provence';
                })
            )
            ->willReturn('rendered template');

        $response = $this->controller->index();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShow(): void
    {
        $product = $this->createMock(Product::class);

        $this->productService->expects($this->once())
            ->method('getProduct')
            ->with(1)
            ->willReturn($product);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'product/show.html.twig',
                $this->callback(function ($params) use ($product) {
                    return $params['product'] === $product &&
                           isset($params['site']) &&
                           $params['site']['siteName'] === 'Maison Provence';
                })
            )
            ->willReturn('rendered template');

        $response = $this->controller->show(1);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShowNotFound(): void
    {
        $this->productService->expects($this->once())
            ->method('getProduct')
            ->with(1)
            ->willThrowException(new \Exception('Product not found'));

        $this->router->expects($this->once())
            ->method('generate')
            ->with('app_product_index')
            ->willReturn('/products');

        $response = $this->controller->show(1);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($this->session->getFlashBag()->has('error'));
        $this->assertEquals(
            ['Le produit demandé n\'existe pas.'],
            $this->session->getFlashBag()->get('error')
        );
    }
}
