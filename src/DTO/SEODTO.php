<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SEODTO
{
    #[Assert\Length(max: 60, maxMessage: 'Le titre meta ne doit pas dépasser {{ limit }} caractères')]
    private ?string $metaTitle = null;

    #[Assert\Length(max: 160, maxMessage: 'La description meta ne doit pas dépasser {{ limit }} caractères')]
    private ?string $metaDescription = null;

    private ?string $metaKeywords = null;
    private ?bool $indexable = true;
    private ?bool $followable = true;
    private ?string $canonicalUrl = null;

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?array $metaKeywords): self
    {
        if (null !== $metaKeywords) {
            // Convertir le tableau en chaîne
            $metaKeywords = implode(', ', array_filter($metaKeywords));
        }

        if (null !== $metaKeywords) {
            // Nettoyage des espaces superflus
            $metaKeywords = preg_replace('/\s*,\s*/', ', ', trim($metaKeywords));
        }

        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function isIndexable(): ?bool
    {
        return $this->indexable;
    }

    public function setIndexable(?bool $indexable): self
    {
        $this->indexable = $indexable;

        return $this;
    }

    public function isFollowable(): ?bool
    {
        return $this->followable;
    }

    public function setFollowable(?bool $followable): self
    {
        $this->followable = $followable;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;

        return $this;
    }
}
