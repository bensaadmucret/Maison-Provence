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

    #[ORM\Column(type: 'boolean')]
    private bool $isEcommerceEnabled = true;

    #[Assert\File(
        maxSize: '1024k',
        mimeTypes: ['image/x-icon', 'image/png', 'image/jpeg', 'image/vnd.microsoft.icon'],
        mimeTypesMessage: 'Veuillez télécharger un fichier au format valide (ico, png)',
    )]
    private ?File $faviconFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $favicon = null;

    #[Assert\File(
        maxSize: '2048k',
        mimeTypes: ['image/png', 'image/jpeg', 'image/svg+xml'],
        mimeTypesMessage: 'Veuillez télécharger un fichier au format valide (png, jpg, svg)',
    )]
    private ?File $logoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $analyticsTrackingId = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $currencyCode = 'EUR';

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $defaultLanguage = 'fr';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxProductsPerPage = 12;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $enableWishlist = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $enableCompareProducts = true;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => '100.00'])]
    private string $freeShippingThreshold = '100.00';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $defaultShippingMethod = 'standard';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $acceptedPaymentMethods = ['carte', 'paypal', 'virement'];

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $gdprComplianceEnabled = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $cookieConsentRequired = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $newsletterEnabled = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxNewsletterSubscriptions = 5000;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $cacheEnabled = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cacheDuration = 3600;

    public function __construct()
    {
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAnalyticsTrackingId(): ?string
    {
        return $this->analyticsTrackingId;
    }

    public function setAnalyticsTrackingId(?string $analyticsTrackingId): static
    {
        $this->analyticsTrackingId = $analyticsTrackingId;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): static
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getDefaultLanguage(): ?string
    {
        return $this->defaultLanguage;
    }

    public function setDefaultLanguage(?string $defaultLanguage): static
    {
        $this->defaultLanguage = $defaultLanguage;

        return $this;
    }

    public function getMaxProductsPerPage(): ?int
    {
        return $this->maxProductsPerPage;
    }

    public function setMaxProductsPerPage(?int $maxProductsPerPage): static
    {
        $this->maxProductsPerPage = $maxProductsPerPage;

        return $this;
    }

    public function isEnableWishlist(): ?bool
    {
        return $this->enableWishlist;
    }

    public function setEnableWishlist(?bool $enableWishlist): static
    {
        $this->enableWishlist = $enableWishlist;

        return $this;
    }

    public function isEnableCompareProducts(): ?bool
    {
        return $this->enableCompareProducts;
    }

    public function setEnableCompareProducts(?bool $enableCompareProducts): static
    {
        $this->enableCompareProducts = $enableCompareProducts;

        return $this;
    }

    public function getFreeShippingThreshold(): float
    {
        return floatval($this->freeShippingThreshold);
    }

    public function setFreeShippingThreshold(float $freeShippingThreshold): static
    {
        $this->freeShippingThreshold = (string)$freeShippingThreshold;
        return $this;
    }

    public function getDefaultShippingMethod(): ?string
    {
        return $this->defaultShippingMethod;
    }

    public function setDefaultShippingMethod(?string $defaultShippingMethod): static
    {
        $this->defaultShippingMethod = $defaultShippingMethod;

        return $this;
    }

    public function getAcceptedPaymentMethods(): ?array
    {
        return $this->acceptedPaymentMethods;
    }

    public function setAcceptedPaymentMethods(?array $acceptedPaymentMethods): static
    {
        $this->acceptedPaymentMethods = $acceptedPaymentMethods;

        return $this;
    }

    public function isGdprComplianceEnabled(): ?bool
    {
        return $this->gdprComplianceEnabled;
    }

    public function setGdprComplianceEnabled(?bool $gdprComplianceEnabled): static
    {
        $this->gdprComplianceEnabled = $gdprComplianceEnabled;

        return $this;
    }

    public function isCookieConsentRequired(): ?bool
    {
        return $this->cookieConsentRequired;
    }

    public function setCookieConsentRequired(?bool $cookieConsentRequired): static
    {
        $this->cookieConsentRequired = $cookieConsentRequired;

        return $this;
    }

    public function isNewsletterEnabled(): ?bool
    {
        return $this->newsletterEnabled;
    }

    public function setNewsletterEnabled(?bool $newsletterEnabled): static
    {
        $this->newsletterEnabled = $newsletterEnabled;

        return $this;
    }

    public function getMaxNewsletterSubscriptions(): ?int
    {
        return $this->maxNewsletterSubscriptions;
    }

    public function setMaxNewsletterSubscriptions(?int $maxNewsletterSubscriptions): static
    {
        $this->maxNewsletterSubscriptions = $maxNewsletterSubscriptions;

        return $this;
    }

    public function isCacheEnabled(): ?bool
    {
        return $this->cacheEnabled;
    }

    public function setCacheEnabled(?bool $cacheEnabled): static
    {
        $this->cacheEnabled = $cacheEnabled;

        return $this;
    }

    public function getCacheDuration(): ?int
    {
        return $this->cacheDuration;
    }

    public function setCacheDuration(?int $cacheDuration): static
    {
        $this->cacheDuration = $cacheDuration;

        return $this;
    }
}
