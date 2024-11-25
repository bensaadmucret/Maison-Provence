<?php

namespace App\DTO;

class SEODTO
{
    private ?string $metaTitle = null;
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

    public function setMetaKeywords(?string $metaKeywords): self
    {
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
