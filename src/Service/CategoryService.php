<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private SluggerInterface $slugger
    ) {}

    public function ensureUncategorizedCategory(): Category
    {
        // Try to find existing Uncategorized category
        $uncategorizedCategory = $this->categoryRepository->findOneBy(['name' => 'Uncategorized']);
        
        if ($uncategorizedCategory) {
            return $uncategorizedCategory;
        }

        // Create new Uncategorized category
        $uncategorizedCategory = new Category();
        $uncategorizedCategory->setName('Uncategorized');
        $uncategorizedCategory->setDescription('Default category for products without a specific category');
        
        // Generate slug
        $slug = $this->slugger->slug(strtolower($uncategorizedCategory->getName()))->toString();
        $uncategorizedCategory->setSlug($slug);

        // Set timestamps
        $now = new \DateTimeImmutable();
        $uncategorizedCategory->setCreatedAt($now);
        $uncategorizedCategory->setUpdatedAt($now);

        // Set level (root category)
        $uncategorizedCategory->setLevel(0);

        // Persist and flush
        $this->entityManager->persist($uncategorizedCategory);
        $this->entityManager->flush();

        return $uncategorizedCategory;
    }
}
