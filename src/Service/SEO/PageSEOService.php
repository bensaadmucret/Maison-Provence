<?php

namespace App\Service\SEO;

use App\DTO\SEODTO;
use App\Entity\LegalPage;
use App\Entity\PageSEO;

class PageSEOService extends AbstractSEOService
{
    public function createOrUpdatePageSEO(LegalPage $page, SEODTO $seoDTO): PageSEO
    {
        $seo = $page->getSeo() ?? new PageSEO();

        // Validation des métadonnées SEO
        $seoErrors = $this->validateSEOMetadata($seoDTO);
        if (!empty($seoErrors)) {
            throw new \InvalidArgumentException(implode(', ', $seoErrors));
        }

        // Mise à jour des champs SEO
        $this->updateSEOFields($seo, $seoDTO);

        // Configuration spécifique aux pages
        $seo->setIdentifier($page->getSlug());
        $this->setCanonicalUrl($seo, $seoDTO->getCanonicalUrl() ??
            $this->urlGenerator->generate('legal_page_show', ['slug' => $page->getSlug()]));

        // Persistance
        if (!$page->getSeo()) {
            $page->setSeo($seo);
            $this->entityManager->persist($seo);
        }

        return $seo;
    }

    public function deleteSEO(LegalPage $page): void
    {
        if ($seo = $page->getSeo()) {
            $page->setSeo(null);
            $this->entityManager->remove($seo);
        }
    }
}
