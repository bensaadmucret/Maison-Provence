<?php

namespace App\Entity;

use App\Repository\SEORepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SEORepository::class)]
#[ORM\Table(name: 'seo')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'product' => ProductSEO::class,
    'category' => CategorySEO::class,
    'page' => PageSEO::class
])]
abstract class SEO
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60, maxMessage: 'Le titre meta ne doit pas dépasser {{ limit }} caractères')]
    private ?string $metaTitle = null;

    #[ORM\Column(length: 160, nullable: true)]
    #[Assert\Length(max: 160, maxMessage: 'La description meta ne doit pas dépasser {{ limit }} caractères')]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $canonicalUrl = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $metaKeywords = [];

    #[ORM\Column]
    private bool $indexable = true;

    #[ORM\Column]
    private bool $followable = true;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $openGraphData = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle !== null ? substr($metaTitle, 0, 60) : null;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription !== null ? substr($metaDescription, 0, 160) : null;
        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    public function getMetaKeywords(): array
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(array $metaKeywords): static
    {
        $this->metaKeywords = array_unique($metaKeywords);
        return $this;
    }

    public function isIndexable(): bool
    {
        return $this->indexable;
    }

    public function setIndexable(bool $indexable): static
    {
        $this->indexable = $indexable;
        return $this;
    }

    public function isFollowable(): bool
    {
        return $this->followable;
    }

    public function setFollowable(bool $followable): static
    {
        $this->followable = $followable;
        return $this;
    }

    public function getOpenGraphData(): array
    {
        return $this->openGraphData;
    }

    public function setOpenGraphData(array $openGraphData): static
    {
        // Filter out invalid OG properties
        $this->openGraphData = array_filter(
            $openGraphData,
            fn($key) => str_starts_with($key, 'og:'),
            ARRAY_FILTER_USE_KEY
        );
        return $this;
    }
}
