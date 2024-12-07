<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:initialize-uploads',
    description: 'Initialise les dossiers d\'upload pour les images',
)]
class InitializeUploadsCommand extends Command
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct();
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $uploadDir = '/public/uploads/images';
        $fullPath = $this->projectDir.$uploadDir;

        if (!file_exists($fullPath)) {
            if (mkdir($fullPath, 0755, true)) {
                $io->success(sprintf('Dossier créé : %s', $uploadDir));
            } else {
                $io->error(sprintf('Impossible de créer le dossier : %s', $uploadDir));

                return Command::FAILURE;
            }
        } else {
            // S'assurer que les permissions sont correctes
            chmod($fullPath, 0755);
            $io->note(sprintf('Le dossier existe déjà : %s', $uploadDir));
        }

        $io->success('Le dossier d\'upload a été initialisé.');

        return Command::SUCCESS;
    }
}
