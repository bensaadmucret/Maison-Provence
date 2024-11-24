<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\CategorySEO;
use App\Entity\Media;
use App\Entity\MediaCollection;
use App\Entity\Product;
use App\Entity\ProductSEO;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HttpFoundation\File\File;

class ProductFixtures extends Fixture
{
    private const CATEGORIES = [
        'Huiles d\'olive' => [
            'description' => 'Nos huiles d\'olive de Provence, pressées avec soin pour préserver toute leur saveur.',
            'products' => [
                'Huile d\'olive extra vierge',
                'Huile d\'olive à la truffe',
                'Huile d\'olive aux herbes de Provence',
                'Huile d\'olive bio',
            ]
        ],
        'Vins' => [
            'description' => 'Une sélection des meilleurs vins de Provence, rouges, blancs et rosés.',
            'products' => [
                'Côtes de Provence Rouge',
                'Bandol Rosé',
                'Cassis Blanc',
                'Palette Rouge',
            ]
        ],
        'Épices et Herbes' => [
            'description' => 'Les herbes et épices qui font la renommée de la cuisine provençale.',
            'products' => [
                'Herbes de Provence',
                'Safran de Provence',
                'Mélange pour Bouillabaisse',
                'Thym sauvage',
            ]
        ],
        'Miels' => [
            'description' => 'Des miels artisanaux récoltés dans les plus beaux terroirs de Provence.',
            'products' => [
                'Miel de Lavande',
                'Miel de Romarin',
                'Miel toutes fleurs',
                'Miel de Thym',
            ]
        ],
    ];

    private const PRODUCT_IMAGES = [
        'Huiles d\'olive' => [
            'width' => 800,
            'height' => 600,
            'background' => '#F4E5D7',
            'text_color' => '#6B4423'
        ],
        'Vins' => [
            'width' => 800,
            'height' => 600,
            'background' => '#E8D5D5',
            'text_color' => '#722F37'
        ],
        'Épices et Herbes' => [
            'width' => 800,
            'height' => 600,
            'background' => '#E5EFE5',
            'text_color' => '#2D5A27'
        ],
        'Miels' => [
            'width' => 800,
            'height' => 600,
            'background' => '#FFF3D6',
            'text_color' => '#B8860B'
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $imageManager = new ImageManager(new Driver());

        // Création du dossier d'upload s'il n'existe pas
        $uploadDir = __DIR__ . '/../../public/uploads/products';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Création des catégories
        foreach (self::CATEGORIES as $categoryName => $categoryData) {
            $category = new Category();
            $category->setName($categoryName);
            $category->setDescription($categoryData['description']);
            $category->setSlug($this->slugify($categoryName));
            $category->setCreatedAt(new \DateTimeImmutable());
            $category->setUpdatedAt(new \DateTimeImmutable());

            // SEO pour la catégorie
            $seo = new CategorySEO();
            $seo->setMetaTitle($categoryName . ' - Maison Provence');
            $seo->setMetaDescription(substr($categoryData['description'], 0, 155));
            $seo->setMetaKeywords([$categoryName, 'Provence', 'Artisanal', 'Terroir']);
            $seo->setCategory($category);
            $category->setSeo($seo);
            $manager->persist($seo);

            $manager->persist($category);

            // Création des produits pour cette catégorie
            foreach ($categoryData['products'] as $productName) {
                $product = new Product();
                $product->setName($productName);
                $product->setDescription($faker->paragraphs(3, true));
                $product->setPrice($faker->randomFloat(2, 10, 100));
                $product->setSlug($this->slugify($productName));
                $product->setCategory($category);
                $product->setCreatedAt(new \DateTimeImmutable());
                $product->setUpdatedAt(new \DateTimeImmutable());
                $product->setStock($faker->numberBetween(10, 100));

                // SEO pour le produit
                $productSeo = new ProductSEO();
                $productSeo->setMetaTitle($productName . ' - ' . $categoryName . ' - Maison Provence');
                $productSeo->setMetaDescription($faker->text(155));
                $productSeo->setMetaKeywords([$productName, $categoryName, 'Provence', 'Artisanal']);
                $productSeo->setProduct($product);
                $manager->persist($productSeo);

                // Collection de médias pour le produit
                $mediaCollection = new MediaCollection();
                $mediaCollection->setName($productName . ' - Images');
                $mediaCollection->setType('product_gallery');
                $manager->persist($mediaCollection);

                // Ajout d'images au produit
                $numImages = $faker->numberBetween(1, 3);
                for ($i = 0; $i < $numImages; $i++) {
                    $media = new Media();
                    $mediaName = $this->slugify($productName) . '-' . ($i + 1) . '.jpg';
                    $media->setTitle($productName . ' - Image ' . ($i + 1));
                    $media->setAlt($productName . ' - Vue ' . ($i + 1));
                    $media->setType('image');
                    $media->setCollection($mediaCollection);
                    $media->setProduct($product);
                    
                    // Génération de l'image
                    $imageConfig = self::PRODUCT_IMAGES[$categoryName];
                    $image = $imageManager->create($imageConfig['width'], $imageConfig['height']);
                    
                    // Fond de couleur
                    $image->fill($imageConfig['background']);
                    
                    // Ajout du nom du produit
                    $image->text($productName, $imageConfig['width'] / 2, $imageConfig['height'] / 2, function ($font) use ($imageConfig) {
                        $font->color($imageConfig['text_color']);
                        $font->align('center');
                        $font->valign('middle');
                        $font->size(40);
                    });
                    
                    // Sauvegarde de l'image
                    $image->save($uploadDir . '/' . $mediaName);
                    $media->setFilename('uploads/products/' . $mediaName);
                    
                    $manager->persist($media);
                    $product->addMedium($media);
                }

                $manager->persist($product);
            }
        }

        $manager->flush();
    }

    private function slugify(string $text): string
    {
        // Remplacer les caractères non alphanumériques par des tirets
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Translitérer
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Supprimer les caractères indésirables
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Supprimer les tirets en début et fin
        $text = trim($text, '-');

        // Remplacer les tirets multiples
        $text = preg_replace('~-+~', '-', $text);

        // Mettre en minuscules
        return strtolower($text);
    }
}
