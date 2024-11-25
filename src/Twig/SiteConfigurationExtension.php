<?php

namespace App\Twig;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SiteConfigurationExtension extends AbstractExtension implements GlobalsInterface
{
    private ?SiteConfiguration $configuration = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getGlobals(): array
    {
        if (null === $this->configuration) {
            $this->configuration = $this->entityManager
                ->getRepository(SiteConfiguration::class)
                ->findOneBy([]);
        }

        return [
            'site_configuration' => $this->configuration,
        ];
    }
}
