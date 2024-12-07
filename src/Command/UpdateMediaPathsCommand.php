<?php

namespace App\Command;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:update-media-paths',
    description: 'Met à jour les chemins des médias vers la nouvelle structure simplifiée',
)]
class UpdateMediaPathsCommand extends Command
{
    private string $projectDir;

    public function __construct(
        private EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mediaRepository = $this->entityManager->getRepository(Media::class);
        $allMedia = $mediaRepository->findAll();

        $updatedCount = 0;
        $uploadDir = $this->projectDir.'/public/uploads/images';

        // Créer le dossier de destination s'il n'existe pas
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            $io->note('Dossier de destination créé : '.$uploadDir);
        }

        foreach ($allMedia as $media) {
            $filename = $media->getFilename();
            if (!$filename) {
                continue;
            }

            // Vérifier si le chemin contient encore l'ancien format
            if (str_contains($filename, 'products/')) {
                // Extraire juste le nom du fichier sans le chemin products/
                $newFilename = basename($filename);
                $media->setFilename($newFilename);
                ++$updatedCount;
                $io->note(sprintf('Mise à jour du chemin : %s -> %s', $filename, $newFilename));
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d chemins de médias ont été mis à jour.', $updatedCount));
        } else {
            $io->info('Aucun chemin de média n\'avait besoin d\'être mis à jour.');
        }

        return Command::SUCCESS;
    }
}
