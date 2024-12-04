<?php

namespace App\Twig;

use App\Service\SiteConfigurationService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SiteConfigurationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private SiteConfigurationService $configurationService
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'site_config' => $this->configurationService->getConfiguration(),
        ];
    }
}
