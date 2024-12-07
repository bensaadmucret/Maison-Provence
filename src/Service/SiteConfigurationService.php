<?php

namespace App\Service;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;

class SiteConfigurationService
{
    private ?SiteConfiguration $configuration = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getConfiguration(): SiteConfiguration
    {
        if (null === $this->configuration) {
            $this->configuration = $this->entityManager->getRepository(SiteConfiguration::class)->findOneBy([])
                ?? $this->createDefaultConfiguration();
        }

        return $this->configuration;
    }

    public function hasConfiguration(): bool
    {
        return $this->entityManager->getRepository(SiteConfiguration::class)->count([]) > 0;
    }

    public function createDefaultConfiguration(): SiteConfiguration
    {
        $config = new SiteConfiguration();

        // Paramètres de base du site
        $config->setSiteName('Maison Provence');
        $config->setMaintenanceMode(false);
        $config->setMaintenanceMessage('Le site est actuellement en maintenance. Nous serons bientôt de retour.');
        $config->setContactEmail('contact@maison-lavande-provence.fr');

        // Configuration e-commerce
        $config->setIsEcommerceEnabled(true);
        $config->setEcommerceDisabledMessage('La boutique en ligne est temporairement désactivée.');

        // Paramètres avancés
        $config->setAnalyticsTrackingId('UA-XXXXXXXX-X');
        $config->setCurrencyCode('EUR');
        $config->setDefaultLanguage('fr');
        $config->setMaxProductsPerPage(12);
        $config->setEnableWishlist(true);
        $config->setEnableCompareProducts(true);

        // Paramètres de livraison et paiement
        $config->setFreeShippingThreshold(100.00);
        $config->setDefaultShippingMethod('standard');
        $config->setAcceptedPaymentMethods(['carte', 'paypal', 'virement']);

        // Paramètres de confidentialité et sécurité
        $config->setGdprComplianceEnabled(true);
        $config->setCookieConsentRequired(true);

        // Paramètres de communication
        $config->setNewsletterEnabled(true);
        $config->setMaxNewsletterSubscriptions(5000);

        // Paramètres de performance
        $config->setCacheEnabled(true);
        $config->setCacheDuration(3600); // 1 heure

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }

    // Méthodes pour mettre à jour la configuration
    public function updateConfiguration(array $configData): SiteConfiguration
    {
        $config = $this->getConfiguration();

        foreach ($configData as $key => $value) {
            $setter = 'set'.ucfirst($key);
            if (method_exists($config, $setter)) {
                $config->$setter($value);
            }
        }

        $this->entityManager->flush();

        return $config;
    }

    // Méthode pour valider certains paramètres
    public function validateConfigurationData(array $configData): array
    {
        $errors = [];

        // Validation de l'email de contact
        if (isset($configData['contactEmail'])
            && !filter_var($configData['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email de contact invalide';
        }

        // Validation du seuil de livraison gratuite
        if (isset($configData['freeShippingThreshold'])
            && (!is_numeric($configData['freeShippingThreshold'])
             || $configData['freeShippingThreshold'] < 0)) {
            $errors[] = 'Seuil de livraison gratuite invalide';
        }

        return $errors;
    }
}
