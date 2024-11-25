<?php

namespace App\Service;

use App\DTO\SEODTO;
use App\Entity\Category;
use App\Entity\CategorySEO;
use App\Entity\PageSEO;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Entity\SEO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SEOService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createOrUpdateSEO(Product|Category $entity, SEODTO $seoDTO): void
    {
        $seo = $entity->getSeo() ?? $this->createSEO($entity);

        $seo->setMetaTitle($seoDTO->getMetaTitle() ?? '');
        $seo->setMetaDescription($seoDTO->getMetaDescription() ? substr((string) $seoDTO->getMetaDescription(), 0, 160) : '');
        $this->setMetaKeywords($seo, $seoDTO->getMetaKeywords() ?? []);
        $this->setCanonicalUrl($seo, $seoDTO->getCanonicalUrl());
        $seo->setIndexable($seoDTO->isIndexable() ?? true);
        $seo->setFollowable($seoDTO->isFollowable() ?? true);

        if ($entity instanceof Product && $seo instanceof ProductSEO) {
            $this->updateProductSEO($seo, $entity);
        } elseif ($entity instanceof Category && $seo instanceof CategorySEO) {
            $this->updateCategorySEO($seo, $entity);
        }

        // Gestion de l'URL canonique
        if (!$seoDTO->getCanonicalUrl()) {
            $canonicalUrl = '';
            if ($entity instanceof Product) {
                $canonicalUrl = $this->urlGenerator->generate('app_product_show', [
                    'id' => $entity->getId(),
                    'slug' => $entity->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            } elseif ($entity instanceof Category) {
                $canonicalUrl = $this->urlGenerator->generate('app_category_show', [
                    'id' => $entity->getId(),
                    'slug' => $entity->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            $this->setCanonicalUrl($seo, $canonicalUrl);
        }

        $this->entityManager->persist($seo);
        $this->entityManager->flush();
    }

    /**
     * @param Product|Category $entity
     * @return ProductSEO|CategorySEO
     * @throws \InvalidArgumentException
     */
    private function createSEO(Product|Category $entity): ProductSEO|CategorySEO
    {
        if ($entity instanceof Product) {
            $seo = new ProductSEO();
            $seo->setProduct($entity);
            return $seo;
        }
        
        if ($entity instanceof Category) {
            $seo = new CategorySEO();
            $seo->setCategory($entity);
            return $seo;
        }
        
        throw new \InvalidArgumentException('Invalid entity type');
    }

    private function updateProductSEO(ProductSEO $seo, Product $product): void
    {
        $mainImage = $product->getMainImage();
        $ogImage = $mainImage ? $mainImage->getPath() : null;

        $openGraphData = [
            'title' => $product->getName(),
            'description' => $product->getDescription() ?? '',
            'type' => 'product',
            'url' => $seo->getCanonicalUrl() ?? '',
            'image' => $ogImage ?? '',
        ];

        $this->setOpenGraphData($seo, $openGraphData);
    }

    private function updateCategorySEO(CategorySEO $seo, Category $category): void
    {
        $name = $category->getName();
        if ($name === null) {
            return;
        }

        $description = $category->getDescription();
        if ($description !== null) {
            $description = substr($description, 0, 160);
        }

        $openGraphData = [
            'title' => $name,
            'description' => $description ?? '',
            'type' => 'category',
            'url' => $seo->getCanonicalUrl() ?? '',
        ];

        $this->setOpenGraphData($seo, $openGraphData);
    }

    public function generateProductSEO(Product $product): ProductSEO
    {
        $seo = new ProductSEO();
        
        $title = $product->getName();
        $description = $product->getDescription();
        if ($description !== null) {
            $description = substr($description, 0, 160);
        }
        
        $mainImage = $product->getMainImage();
        $imageUrl = $mainImage ? $mainImage->getPath() : null;
        
        $seo->setMetaTitle($title);
        $seo->setMetaDescription($description ?? '');
        $this->setMetaKeywords($seo, ['produit', 'maison provence', $title]);
        $seo->setIndexable(true);
        $seo->setFollowable(true);
        $seo->setProduct($product);
        
        if ($imageUrl) {
            $seo->setOgImage($imageUrl);
        }
        
        return $seo;
    }

    public function generateCategorySEO(Category $category): CategorySEO
    {
        $seo = new CategorySEO();
        
        $name = $category->getName() ?? '';
        $description = $category->getDescription();
        if ($description !== null) {
            $description = substr($description, 0, 160);
        }
        
        $seo->setMetaTitle($name);
        $seo->setMetaDescription($description ?? '');
        $this->setMetaKeywords($seo, ['catégorie', 'maison provence', $name]);
        $seo->setIndexable(true);
        $seo->setFollowable(true);
        $seo->setCategory($category);
        
        return $seo;
    }

    public function generateMetaTitle(Product $product): string
    {
        $seo = $product->getSeo();
        if ($seo && $seo->getMetaTitle()) {
            return $seo->getMetaTitle();
        }

        $category = $product->getCategory();
        if (!$category) {
            return $product->getName();
        }

        return sprintf('%s - %s', $product->getName(), $category->getName() ?? '');
    }

    public function generateMetaDescription(Product $product): string
    {
        $seo = $product->getSeo();
        if ($seo && $seo->getMetaDescription()) {
            return $seo->getMetaDescription();
        }

        $description = $product->getDescription();
        if ($description === null) {
            return '';
        }

        return substr($description, 0, 160);
    }

    public function generateRobotsMeta(Product $product): string
    {
        $seo = $product->getSeo();
        if (!$seo) {
            return 'index, follow';
        }

        $directives = [];

        if ($seo->isIndexable()) {
            $directives[] = 'index';
        } else {
            $directives[] = 'noindex';
        }

        if ($seo->isFollowable()) {
            $directives[] = 'follow';
        } else {
            $directives[] = 'nofollow';
        }

        return implode(', ', $directives);
    }

    /**
     * @param string|array<string> $keywords
     */
    public function setMetaKeywords(SEO $seo, string|array $keywords): void
    {
        if (is_string($keywords)) {
            $keywords = array_map('trim', explode(',', $keywords));
        }

        $seo->setMetaKeywords($keywords);
        $this->entityManager->flush();
    }

    public function setMetaDescription(SEO $seo, string $description): void
    {
        $seo->setMetaDescription($description);
        $this->entityManager->flush();
    }

    public function setCanonicalUrl(SEO $seo, string $url): void
    {
        $seo->setCanonicalUrl($url);
        $this->entityManager->flush();
    }

    public function setRobots(SEO $seo, string $robots): void
    {
        $seo->setRobots($robots);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, string> $data
     */
    public function setOpenGraphData(SEO $seo, array $data): void
    {
        $seo->setOpenGraphData($data);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, string> $data
     */
    public function setTwitterCardData(SEO $seo, array $data): void
    {
        $seo->setTwitterCardData($data);
        $this->entityManager->flush();
    }
}
