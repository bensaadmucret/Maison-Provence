<?php

namespace App\Command;

use App\Service\SiteConfigurationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-site-configuration',
    description: 'Initialize site configuration if it does not exist',
)]
class InitSiteConfigurationCommand extends Command
{
    public function __construct(
        private SiteConfigurationService $configService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if ($this->configService->hasConfiguration()) {
                $io->warning('Site configuration already exists.');
                return Command::SUCCESS;
            }

            $config = $this->configService->createDefaultConfiguration();
            
            $io->success('Site configuration has been initialized successfully.');
            $io->table(
                ['Setting', 'Value'],
                [
                    ['Site Name', $config->getSiteName()],
                    ['Contact Email', $config->getContactEmail()],
                    ['E-commerce Enabled', $config->isEcommerceEnabled() ? 'Yes' : 'No'],
                    ['Maintenance Mode', $config->isMaintenanceMode() ? 'Yes' : 'No'],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while initializing the site configuration: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
