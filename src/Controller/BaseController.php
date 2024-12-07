<?php

namespace App\Controller;

use App\Service\SiteConfigurationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends AbstractController
{
    public function __construct(
        protected SiteConfigurationService $configurationService,
    ) {
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $parameters['site_config'] = $this->configurationService->getConfiguration();

        return parent::render($view, $parameters, $response);
    }
}
