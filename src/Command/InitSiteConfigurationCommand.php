<?php

namespace App\Command;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $configRepository = $this->entityManager->getRepository(SiteConfiguration::class);
        $existingConfig = $configRepository->findOneBy([]);

        if ($existingConfig) {
            $io->warning('Site configuration already exists.');

            return Command::SUCCESS;
        }

        $config = new SiteConfiguration();
        $config->setSiteName('Maison Provence');
        $config->setMaintenanceMode(false);
        $config->setMaintenanceMessage('Le site est actuellement en maintenance. Nous serons bientôt de retour.');
        $config->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $io->success('Site configuration has been initialized.');

        return Command::SUCCESS;
    }
}
