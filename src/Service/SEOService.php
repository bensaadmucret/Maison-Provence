<?php

namespace App\Service;

use App\DTO\SEODTO;
use App\Entity\Product;
use App\Entity\SEO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SEOService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function createOrUpdateSEO(Product $product, SEODTO $seoDTO): void
    {
        $seo = $product->getSeo() ?? new SEO();

        // Mise à jour des données de base
        $seo->setMetaTitle($seoDTO->getMetaTitle() ?? $product->getName())
            ->setMetaDescription($seoDTO->getMetaDescription() ?? substr($product->getDescription(), 0, 160))
            ->setMetaKeywords($seoDTO->getMetaKeywords())
            ->setIndexable($seoDTO->isIndexable())
            ->setFollowable($seoDTO->isFollowable());

        // Gestion de l'URL canonique
        if ($seoDTO->getCanonicalUrl()) {
            $seo->setCanonicalUrl($seoDTO->getCanonicalUrl());
        } else {
            $canonicalUrl = $this->urlGenerator->generate('app_product_show', [
                'id' => $product->getId(),
                'slug' => $product->getSlug()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $seo->setCanonicalUrl($canonicalUrl);
        }

        // Gestion des données Open Graph
        $openGraphData = [
            'title' => $seoDTO->getMetaTitle() ?? $product->getName(),
            'description' => $seoDTO->getMetaDescription() ?? substr($product->getDescription(), 0, 160),
            'type' => 'product',
            'url' => $seo->getCanonicalUrl()
        ];

        // Si le produit a une image, l'utiliser pour Open Graph
        if ($product->getMedia()->count() > 0) {
            $openGraphData['image'] = $product->getMedia()->first()->getPath();
        }

        $seo->setOpenGraphData($openGraphData);

        // Liaison avec le produit
        if (!$product->getSeo()) {
            $product->setSeo($seo);
            $seo->setProduct($product);
        }

        $this->entityManager->persist($seo);
        $this->entityManager->flush();
    }

    public function generateMetaTitle(Product $product): string
    {
        if ($product->getSeo() && $product->getSeo()->getMetaTitle()) {
            return $product->getSeo()->getMetaTitle();
        }

        return sprintf('%s - %s', $product->getName(), $product->getCategory()->getName());
    }

    public function generateMetaDescription(Product $product): string
    {
        if ($product->getSeo() && $product->getSeo()->getMetaDescription()) {
            return $product->getSeo()->getMetaDescription();
        }

        return substr($product->getDescription(), 0, 160);
    }

    public function generateRobotsMeta(Product $product): string
    {
        if (!$product->getSeo()) {
            return 'index, follow';
        }

        $directives = [];
        
        if ($product->getSeo()->isIndexable()) {
            $directives[] = 'index';
        } else {
            $directives[] = 'noindex';
        }

        if ($product->getSeo()->isFollowable()) {
            $directives[] = 'follow';
        } else {
            $directives[] = 'nofollow';
        }

        return implode(', ', $directives);
    }
}
