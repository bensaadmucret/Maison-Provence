<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    private const CATEGORIES = [
        'Huiles Essentielles' => [
            'description' => 'Huiles essentielles 100% naturelles de Provence',
            'children' => [
                'Huiles Essentielles Bio' => [
                    'description' => 'Huiles essentielles biologiques certifiées',
                ],
                'Huiles Essentielles de Provence' => [
                    'description' => 'Huiles essentielles récoltées dans les champs de Provence',
                ],
            ],
        ],
        'Savons Artisanaux' => [
            'description' => 'Savons fabriqués à la main selon les traditions provençales',
            'children' => [
                'Savons Naturels' => [
                    'description' => 'Savons élaborés avec des ingrédients 100% naturels',
                ],
                'Savons au Lait de Chèvre' => [
                    'description' => 'Savons nourrissants à base de lait de chèvre local',
                ],
            ],
        ],
        'Cosmétiques Naturels' => [
            'description' => 'Produits de beauté élaborés à partir d\'ingrédients locaux',
            'children' => [
                'Crèmes et Sérums' => [
                    'description' => 'Soins visage naturels et anti-âge',
                ],
                'Soins Corps' => [
                    'description' => 'Hydratation et nutrition pour votre peau',
                ],
            ],
        ],
        'Épicerie Fine' => [
            'description' => 'Produits gastronomiques de la région provençale',
            'children' => [
                'Huiles d\'Olive' => [
                    'description' => 'Huiles d\'olive extra vierge de Provence',
                ],
                'Miels et Confitures' => [
                    'description' => 'Miels et confitures artisanaux',
                ],
            ],
        ],
        'Accessoires' => [
            'description' => 'Accessoires et objets décoratifs inspirés de la Provence',
            'children' => [
                'Linge de Maison' => [
                    'description' => 'Textiles et linge inspirés des traditions provençales',
                ],
                'Décoration' => [
                    'description' => 'Objets décoratifs et artisanaux',
                ],
            ],
        ],
    ];

    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createCategories($manager, $this::CATEGORIES);
        $manager->flush();
    }

    private function createCategories(
        ObjectManager $manager,
        array $categories,
        ?Category $parent = null,
        int $level = 0,
    ): void {
        foreach ($categories as $name => $data) {
            $category = new Category();
            $category
                ->setName($name)
                ->setDescription($data['description'])
                ->setSlug($this->slugger->slug($name)->lower())
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            if ($parent) {
                $category->setParent($parent);
            }

            $manager->persist($category);

            // Recursively create child categories
            if (isset($data['children'])) {
                $this->createCategories(
                    $manager,
                    $data['children'],
                    $category,
                    $level + 1
                );
            }
        }
    }

    public static function getGroups(): array
    {
        return ['categories', 'dev'];
    }
}
