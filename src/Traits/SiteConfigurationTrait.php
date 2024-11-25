<?php

namespace App\Traits;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;

trait SiteConfigurationTrait
{
    private ?SiteConfiguration $siteConfiguration = null;

    protected function getSiteConfiguration(EntityManagerInterface $entityManager): SiteConfiguration
    {
        if (null === $this->siteConfiguration) {
            $this->siteConfiguration = $entityManager
                ->getRepository(SiteConfiguration::class)
                ->findOneBy([]);

            if (null === $this->siteConfiguration) {
                throw new \RuntimeException('Site configuration not found');
            }
        }

        return $this->siteConfiguration;
    }
}
