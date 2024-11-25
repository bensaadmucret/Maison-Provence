<?php

namespace App\Tests\Template;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\SiteConfiguration;
use App\Twig\SiteConfigurationExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class ImportMapExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('importmap', [$this, 'importmap']),
        ];
    }

    public function importmap(string $name): string
    {
        return '<script type="importmap">{"imports":{}}</script>';
    }
}

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'app' => (object)[
                'user' => null,
                'request' => null,
                'session' => null,
                'flashes' => [],
                'environment' => 'test',
                'debug' => true
            ]
        ];
    }
}

class ProductShowTemplateTest extends TestCase
{
    private Environment $twig;
    private SiteConfigurationExtension $siteConfigExtension;
    private RoutingExtension $routingExtension;
    private AssetExtension $assetExtension;
    private Product $previousProduct;
    private Product $nextProduct;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader([
            __DIR__ . '/../../templates'
        ]);
        
        $this->twig = new Environment($loader, [
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'optimizations' => 0
        ]);

        // Mock EntityManager et Repository pour SiteConfigurationExtension
        $siteConfig = new SiteConfiguration();
        $siteConfig->setSiteName('Maison Provence Test');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')
            ->willReturn($siteConfig);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturn($repository);

        $this->siteConfigExtension = new SiteConfigurationExtension($entityManager);
        
        // Créer une vraie instance de RoutingExtension avec un générateur d'URL mocké
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->willReturn('#');
        $this->routingExtension = new RoutingExtension($urlGenerator);
        
        // Créer une vraie instance de AssetExtension
        $defaultPackage = new Package(new EmptyVersionStrategy());
        $packages = new Packages($defaultPackage);
        $this->assetExtension = new AssetExtension($packages);

        // Mock RuntimeLoader for Twig extensions
        $runtimeLoader = $this->createMock(RuntimeLoaderInterface::class);
        $this->twig->addRuntimeLoader($runtimeLoader);
        
        // Add all required extensions
        $this->twig->addExtension(new AppExtension());
        $this->twig->addExtension(new ImportMapExtension());
        $this->twig->addExtension($this->siteConfigExtension);
        $this->twig->addExtension($this->routingExtension);
        $this->twig->addExtension($this->assetExtension);

        // Désactiver l'échappement HTML pour les tests
        $this->twig->getExtension(\Twig\Extension\EscaperExtension::class)->setDefaultStrategy(false);

        // Créer des produits de navigation par défaut
        $this->previousProduct = new Product();
        $this->previousProduct->setName('Produit précédent');
        $this->previousProduct->setSlug('produit-precedent');

        $this->nextProduct = new Product();
        $this->nextProduct->setName('Produit suivant');
        $this->nextProduct->setSlug('produit-suivant');
    }

    public function testRenderProductWithAllData(): void
    {
        // Créer un produit complet
        $product = new Product();
        $product->setName('Cassis Blanc');
        $product->setDescription('Description du produit');
        $product->setPrice(63.11);
        $product->setStock(43);
        $product->setSlug('cassis-blanc');
        $product->setIsActive(true);

        // Ajouter une catégorie
        $category = new Category();
        $category->setName('Vins');
        $category->setSlug('vins');
        $product->setCategory($category);

        // Créer des produits similaires
        $similarProducts = [];
        for ($i = 1; $i <= 3; $i++) {
            $similarProduct = new Product();
            $similarProduct->setName("Produit similaire $i");
            $similarProduct->setPrice(50 + $i);
            $similarProduct->setSlug("produit-similaire-$i");
            $similarProducts[] = $similarProduct;
        }

        // Rendre le template avec toutes les variables nécessaires
        $html = $this->twig->render('product/show.html.twig', [
            'product' => $product,
            'similarProducts' => $similarProducts,
            'previousProduct' => $this->previousProduct,
            'nextProduct' => $this->nextProduct,
        ]);

        // Assertions
        $this->assertStringContainsString('Cassis Blanc', $html);
        $this->assertStringContainsString('63,11 €', $html);
        $this->assertStringContainsString('43', $html);
        $this->assertStringContainsString('Vins', $html);
        $this->assertStringContainsString('Produits similaires', $html);
        $this->assertStringContainsString('Produit similaire 1', $html);
    }

    public function testRenderProductWithoutImage(): void
    {
        $product = new Product();
        $product->setName('Produit sans image');
        $product->setPrice(50.00);
        $product->setStock(10);
        $product->setSlug('produit-sans-image');
        $product->setIsActive(true);

        $html = $this->twig->render('product/show.html.twig', [
            'product' => $product,
            'similarProducts' => [],
            'previousProduct' => $this->previousProduct,
            'nextProduct' => $this->nextProduct,
        ]);

        $this->assertStringContainsString('Aucune image disponible', $html);
    }

    public function testRenderProductWithoutCategory(): void
    {
        $product = new Product();
        $product->setName('Produit sans catégorie');
        $product->setPrice(50.00);
        $product->setStock(10);
        $product->setSlug('produit-sans-categorie');
        $product->setIsActive(true);

        $html = $this->twig->render('product/show.html.twig', [
            'product' => $product,
            'similarProducts' => [],
            'previousProduct' => $this->previousProduct,
            'nextProduct' => $this->nextProduct,
        ]);

        $this->assertStringNotContainsString('Catégorie', $html);
    }

    public function testRenderProductOutOfStock(): void
    {
        $product = new Product();
        $product->setName('Produit en rupture');
        $product->setPrice(50.00);
        $product->setStock(0);
        $product->setSlug('produit-en-rupture');
        $product->setIsActive(true);

        $html = $this->twig->render('product/show.html.twig', [
            'product' => $product,
            'similarProducts' => [],
            'previousProduct' => $this->previousProduct,
            'nextProduct' => $this->nextProduct,
        ]);

        $this->assertStringContainsString('Produit temporairement indisponible', $html);
        $this->assertStringNotContainsString('Ajouter au panier', $html);
    }
}
