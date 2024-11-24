<?php

namespace App\Traits;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;

trait SiteConfigurationTrait
{
    private ?SiteConfiguration $siteConfiguration = null;

    protected function getSiteConfiguration(EntityManagerInterface $entityManager): SiteConfiguration
    {
        if ($this->siteConfiguration === null) {
            $this->siteConfiguration = $entityManager
                ->getRepository(SiteConfiguration::class)
                ->findOneBy([]);

            if ($this->siteConfiguration === null) {
                throw new \RuntimeException('Site configuration not found');
            }
        }

        return $this->siteConfiguration;
    }
}
