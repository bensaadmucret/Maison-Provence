<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Media;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Service\PexelsImageService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const PRODUCT_COUNT = 20;
    private const IMAGE_QUERIES = [
        'lavender field', 'provence landscape', 'french countryside',
        'olive grove', 'mediterranean herbs', 'rustic provence',
    ];

    public function __construct(
        private readonly PexelsImageService $pexelsImageService,
        private readonly SluggerInterface $slugger,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');
        $categories = $this->getCategories($manager);

        for ($i = 0; $i < self::PRODUCT_COUNT; ++$i) {
            $product = $this->createProduct($faker, $categories, $manager);
            $manager->persist($product);
        }

        $manager->flush();
    }

    private function createProduct(
        \Faker\Generator $faker,
        array $categories,
        ObjectManager $manager,
    ): Product {
        // Sélection aléatoire de la catégorie
        $category = $faker->randomElement($categories);

        // Génération du nom du produit avec un suffixe unique
        $baseNames = [
            'Savon de Marseille', 'Huile d\'Olive', 'Herbes de Provence',
            'Lavande Séchée', 'Miel de Lavande', 'Tapenade',
            'Calissons d\'Aix', 'Navettes de Marseille', 'Nougat de Provence',
        ];
        $productName = $faker->randomElement($baseNames).' '.uniqid();

        $product = (new Product())
            ->setName($productName)
            ->setDescription($faker->paragraph())
            ->setPrice($faker->randomFloat(2, 10, 200))
            ->setStock($faker->numberBetween(0, 100))
            ->setSlug($this->slugger->slug($productName)->lower())
            ->setCategory($category)
            ->setIsFeatured(rand(0, 10) < 3) // 30% de chance d'être mis en avant
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        // Création du Media pour l'image
        if ($filename = $this->downloadAndOptimizeImage($faker->randomElement(self::IMAGE_QUERIES), $productName)) {
            $media = new Media();
            $media->setProduct($product)
                ->setFilename($filename)
                ->setAlt($productName)
                ->setTitle($productName)
                ->setPosition(0)
                ->setType('product')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            // Créer un objet File à partir du fichier physique
            $filePath = $this->projectDir.'/public/uploads/images/'.$filename;
            if (file_exists($filePath)) {
                $media->setImageFile(new File($filePath));
            }

            $manager->persist($media);
        }

        // Création du SEO pour le produit
        $seo = new ProductSEO();
        $description = $product->getDescription();
        $seo->setProduct($product)
            ->setMetaTitle(substr($productName, 0, 60))
            ->setMetaDescription(substr($description, 0, 160))
            ->setCanonicalUrl($this->generateCanonicalUrl($product))
            ->setMetaKeywords($this->generateMetaKeywords($productName, $category))
            ->setIndexable(true)
            ->setFollowable(true)
            ->setOpenGraphData([
                'title' => $productName,
                'description' => substr($description, 0, 160),
                'type' => 'product',
                'url' => $this->generateCanonicalUrl($product),
                'image' => $filename ? 'uploads/images/'.$filename : null,
            ]);

        $product->setSeo($seo);

        return $product;
    }

    private function getCategories(ObjectManager $manager): array
    {
        return $manager->getRepository(Category::class)->findAll();
    }

    public static function getGroups(): array
    {
        return ['products', 'dev'];
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }

    private function generateCanonicalUrl(Product $product): string
    {
        // Vérifier si le produit a un slug valide
        if ($product->getSlug()) {
            try {
                return $this->urlGenerator->generate('app_product_show', [
                    'slug' => $product->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            } catch (\Exception $e) {
                // Fallback to a generic URL if generation fails
                return 'https://maison-provence.fr/produits/'.$product->getSlug();
            }
        }

        // URL par défaut si pas de slug
        return 'https://maison-provence.fr/produits';
    }

    private function generateMetaKeywords(string $productName, Category $category): array
    {
        return [
            $productName,
            $category->getName(),
            'Maison Provence',
            'produit provençal',
        ];
    }

    private function downloadAndOptimizeImage(string $imageQuery, string $productName): ?string
    {
        try {
            $images = $this->pexelsImageService->searchImages($imageQuery, 1);
            if (empty($images)) {
                return null;
            }

            $imageUrl = $images[0]['src']['large'];
            $imageContent = file_get_contents($imageUrl);

            if (false === $imageContent) {
                return null;
            }

            // Créer le répertoire s'il n'existe pas
            $uploadDir = $this->projectDir.'/public/uploads/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Générer un nom de fichier unique
            $filename = $this->slugger->slug($productName).'-'.uniqid().'.jpg';
            $filepath = $uploadDir.$filename;

            // Sauvegarder l'image
            file_put_contents($filepath, $imageContent);

            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
