<?php

namespace App\Service\SEO;

use App\DTO\SEODTO;
use App\Entity\SEO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractSEOService
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    protected function validateSEOMetadata(SEODTO $seoDTO): array
    {
        $errors = [];

        if ($seoDTO->getMetaTitle() && strlen($seoDTO->getMetaTitle()) > 60) {
            $errors[] = 'Meta title should not exceed 60 characters';
        }

        if ($seoDTO->getMetaDescription() && strlen($seoDTO->getMetaDescription()) > 160) {
            $errors[] = 'Meta description should not exceed 160 characters';
        }

        return $errors;
    }

    protected function updateSEOFields(SEO $seo, SEODTO $seoDTO): void
    {
        $seo->setMetaTitle($seoDTO->getMetaTitle() ?? '');
        $seo->setMetaDescription($seoDTO->getMetaDescription() ?
            substr($seoDTO->getMetaDescription(), 0, 160) : '');

        $keywords = $seoDTO->getMetaKeywords();
        if (is_array($keywords)) {
            $keywords = implode(', ', array_filter($keywords));
        }
        $seo->setMetaKeywords($keywords ? trim($keywords) : null);

        $seo->setIndexable($seoDTO->isIndexable() ?? true);
        $seo->setFollowable($seoDTO->isFollowable() ?? true);
    }

    protected function setCanonicalUrl(SEO $seo, string $url): void
    {
        if (empty($url)) {
            return;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $seo->setCanonicalUrl($url);
        } else {
            $seo->setCanonicalUrl($this->urlGenerator->generate($url, [], UrlGeneratorInterface::ABSOLUTE_URL));
        }
    }
}
