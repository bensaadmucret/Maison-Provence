<?php

namespace App\Entity;

use App\Repository\LegalPageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalPageRepository::class)]
class LegalPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\OneToOne(inversedBy: 'legalPage', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?PageSEO $seo = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSeo(): ?PageSEO
    {
        return $this->seo;
    }

    public function setSeo(?PageSEO $seo): self
    {
        // Désassocier l'ancienne page SEO si elle existe
        if (null !== $this->seo && $this->seo !== $seo) {
            $oldSeo = $this->seo;
            $this->seo = null;
            $oldSeo->setLegalPage(null);
        }

        // Associer la nouvelle page SEO
        $this->seo = $seo;
        if (null !== $seo && $seo->getLegalPage() !== $this) {
            $seo->setLegalPage($this);
        }

        return $this;
    }
}
