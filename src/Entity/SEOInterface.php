<?php

namespace App\Entity;

interface SEOInterface
{
    public function setProduct(?Product $product): self;
    public function setCanonicalUrl(?string $canonicalUrl): self;
    public function getMetaTitle(): ?string;
    public function getMetaDescription(): ?string;
    public function getCanonicalUrl(): ?string;
    public function setMetaTitle(?string $metaTitle): self;
    public function setMetaDescription(?string $metaDescription): self;
    public function setMetaKeywords(?string $metaKeywords): self;
    public function setIndexable(bool $indexable): self;
    public function setFollowable(bool $followable): self;
    public function setOpenGraphData(array $openGraphData): self;
}
