<?php

namespace App\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;

class SEOMetadata
{
    public function __construct(
        #[Assert\Length(max: 60, maxMessage: 'Le titre meta ne doit pas dépasser {{ limit }} caractères')]
        private readonly ?string $metaTitle,

        #[Assert\Length(max: 160, maxMessage: 'La description meta ne doit pas dépasser {{ limit }} caractères')]
        private readonly ?string $metaDescription,

        private readonly ?string $canonicalUrl,

        #[Assert\Url(message: 'L\'URL canonique doit être une URL valide')]
        private readonly array $metaKeywords = [],

        private readonly bool $indexable = true,

        private readonly bool $followable = true,
    ) {
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function getMetaKeywords(): array
    {
        return $this->metaKeywords;
    }

    public function isIndexable(): bool
    {
        return $this->indexable;
    }

    public function isFollowable(): bool
    {
        return $this->followable;
    }

    public function getFormattedKeywords(): ?string
    {
        if (empty($this->metaKeywords)) {
            return null;
        }

        return implode(', ', array_filter($this->metaKeywords));
    }

    public function getRobotsDirective(): string
    {
        $directives = [];

        if (!$this->indexable) {
            $directives[] = 'noindex';
        }

        if (!$this->followable) {
            $directives[] = 'nofollow';
        }

        return empty($directives) ? 'index, follow' : implode(', ', $directives);
    }
}
