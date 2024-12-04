<?php

namespace App\Service;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;

class SiteConfigurationService
{
    private ?SiteConfiguration $configuration = null;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getConfiguration(): SiteConfiguration
    {
        if ($this->configuration === null) {
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
        $config->setSiteName('Maison Provence');
        $config->setMaintenanceMode(false);
        $config->setMaintenanceMessage('Le site est actuellement en maintenance. Nous serons bientôt de retour.');
        $config->setContactEmail('contact@maison-lavande-provence.fr');
        $config->setIsEcommerceEnabled(true);
        $config->setEcommerceDisabledMessage('La boutique en ligne est temporairement désactivée.');

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }
}
