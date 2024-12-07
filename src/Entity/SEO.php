<?php

namespace App\Entity;

use App\Repository\SEORepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SEORepository::class)]
#[ORM\Table(name: 'seo')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'product' => ProductSEO::class,
    'category' => CategorySEO::class,
    'page' => PageSEO::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class SEO implements SEOInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 70, nullable: true)]
    #[Assert\Length(max: 70, maxMessage: 'Le titre meta ne doit pas dépasser {{ limit }} caractères')]
    private ?string $metaTitle = null;

    #[ORM\Column(length: 200, nullable: true)]
    #[Assert\Length(max: 200, maxMessage: 'La description meta ne doit pas dépasser {{ limit }} caractères')]
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
    #[Assert\Collection(
        fields: [
            'title' => [new Assert\Length(max: 70)],
            'description' => [new Assert\Length(max: 200)],
            'image' => [new Assert\Url()],
            'type' => [new Assert\Choice(['website', 'article', 'product'])],
        ],
        allowExtraFields: false
    )]
    private array $openGraphData = [];

    public function __construct()
    {
        $this->metaKeywords = [];
        $this->openGraphData = [];
        $this->indexable = true;
        $this->followable = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        if (null !== $metaTitle && strlen($metaTitle) > 70) {
            $metaTitle = substr($metaTitle, 0, 70);
        }
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        if (null !== $metaDescription && strlen($metaDescription) > 200) {
            $metaDescription = substr($metaDescription, 0, 200);
        }
        $this->metaDescription = $metaDescription;

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

    public function getMetaKeywords(): array
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(array $metaKeywords): self
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function isIndexable(): bool
    {
        return $this->indexable;
    }

    public function setIndexable(bool $indexable): self
    {
        $this->indexable = $indexable;

        return $this;
    }

    public function isFollowable(): bool
    {
        return $this->followable;
    }

    public function setFollowable(bool $followable): self
    {
        $this->followable = $followable;

        return $this;
    }

    public function getOpenGraphData(): array
    {
        return $this->openGraphData;
    }

    public function setOpenGraphData(array $openGraphData): self
    {
        $this->openGraphData = $openGraphData;

        return $this;
    }

    #[ORM\PostLoad]
    public function onPostLoad(PostLoadEventArgs $event): void
    {
        // No need to modify metaKeywords here as it's already an array
    }
}
