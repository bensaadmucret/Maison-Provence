<?php

namespace App\Entity;

use App\Repository\SiteConfigurationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SiteConfigurationRepository::class)]
class SiteConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $siteName = 'Maison Provence';

    #[ORM\Column(type: 'boolean')]
    private bool $maintenanceMode = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $maintenanceMessage = 'Le site est actuellement en maintenance. Nous serons bientôt de retour.';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ecommerceDisabledMessage = null;

    #[ORM\Column(type: 'boolean', name: 'is_ecommerce_enabled')]
    private ?bool $isEcommerceEnabled = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isEcommerceEnabledNew = false;

    /**
     * @var File|null
     */
    #[Assert\File(
        maxSize: '1024k',
        mimeTypes: ['image/x-icon', 'image/png', 'image/jpeg', 'image/vnd.microsoft.icon'],
        mimeTypesMessage: 'Veuillez télécharger un fichier au format valide (ico, png)',
    )]
    private ?File $faviconFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $favicon = null;

    /**
     * @var File|null
     */
    #[Assert\File(
        maxSize: '2048k',
        mimeTypes: ['image/png', 'image/jpeg', 'image/svg+xml'],
        mimeTypesMessage: 'Veuillez télécharger un fichier au format valide (png, jpg, svg)',
    )]
    private ?File $logoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->maintenanceMode = false;
        $this->isEcommerceEnabled = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function isMaintenanceMode(): ?bool
    {
        return $this->maintenanceMode;
    }

    public function setMaintenanceMode(bool $maintenanceMode): static
    {
        $this->maintenanceMode = $maintenanceMode;

        return $this;
    }

    public function getMaintenanceMessage(): ?string
    {
        return $this->maintenanceMessage;
    }

    public function setMaintenanceMessage(?string $maintenanceMessage): static
    {
        $this->maintenanceMessage = $maintenanceMessage;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getEcommerceDisabledMessage(): ?string
    {
        return $this->ecommerceDisabledMessage;
    }

    public function setEcommerceDisabledMessage(?string $ecommerceDisabledMessage): static
    {
        $this->ecommerceDisabledMessage = $ecommerceDisabledMessage;

        return $this;
    }

    public function isEcommerceEnabled(): ?bool
    {
        return $this->isEcommerceEnabled;
    }

    public function setIsEcommerceEnabled(bool $isEcommerceEnabled): static
    {
        $this->isEcommerceEnabled = $isEcommerceEnabled;

        return $this;
    }

    public function isEcommerceEnabledNew(): bool
    {
        return $this->isEcommerceEnabledNew;
    }

    public function setIsEcommerceEnabledNew(bool $isEcommerceEnabledNew): self
    {
        $this->isEcommerceEnabledNew = $isEcommerceEnabledNew;
        return $this;
    }

    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

    public function setFavicon(?string $favicon): static
    {
        $this->favicon = $favicon;
        return $this;
    }

    public function getFaviconFile(): ?File
    {
        return $this->faviconFile;
    }

    public function setFaviconFile(?File $faviconFile): self
    {
        $this->faviconFile = $faviconFile;
        if ($faviconFile) {
            // Forcer la mise à jour de l'entité même si seul le fichier change
            $this->updatedAt = new \DateTimeImmutable();
            $this->favicon = $faviconFile->getFilename();
        }
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogoFile(?File $logoFile): self
    {
        $this->logoFile = $logoFile;
        if ($logoFile) {
            // Forcer la mise à jour de l'entité même si seul le fichier change
            $this->updatedAt = new \DateTimeImmutable();
            $this->logo = $logoFile->getFilename();
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
