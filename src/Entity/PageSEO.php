<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PageSEO extends SEO
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $identifier = null;

    #[ORM\OneToOne(mappedBy: 'seo', targetEntity: LegalPage::class, cascade: ['persist'])]
    private ?LegalPage $legalPage = null;

    public function __construct()
    {
        parent::__construct();
        $this->route = null;
        $this->identifier = null;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getLegalPage(): ?LegalPage
    {
        return $this->legalPage;
    }

    public function setLegalPage(?LegalPage $legalPage): self
    {
        // Gérer la relation bidirectionnelle
        $this->legalPage = $legalPage;
        if (null !== $legalPage && $legalPage->getSeo() !== $this) {
            $legalPage->setSeo($this);
        }

        return $this;
    }
}
