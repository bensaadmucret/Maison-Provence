<?php

namespace App\Command;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:fix-duplicate-slugs',
    description: 'Fix duplicate product slugs by adding a unique suffix',
)]
class FixDuplicateSlugsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Trouver les slugs en double
        $duplicateSlugs = $this->entityManager->createQuery(
            'SELECT p.slug, COUNT(p.id) as count
             FROM App\Entity\Product p
             GROUP BY p.slug
             HAVING COUNT(p.id) > 1'
        )->getResult();

        if (empty($duplicateSlugs)) {
            $io->success('No duplicate slugs found.');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Found %d duplicate slugs', count($duplicateSlugs)));

        // Pour chaque slug en double
        foreach ($duplicateSlugs as $duplicateSlug) {
            $slug = $duplicateSlug['slug'];
            $io->text(sprintf('Fixing duplicate slug: %s', $slug));

            // Trouver tous les produits avec ce slug
            $products = $this->productRepository->findBy(['slug' => $slug]);

            // Garder le premier produit tel quel, mettre à jour les autres
            for ($i = 1; $i < count($products); ++$i) {
                $product = $products[$i];
                $newSlug = sprintf('%s-%d', $slug, $i);
                $product->setSlug($newSlug);
                $io->text(sprintf(' - Updated product ID %d: new slug = %s', $product->getId(), $newSlug));
            }
        }

        // Sauvegarder les changements
        $this->entityManager->flush();

        $io->success('All duplicate slugs have been fixed.');

        return Command::SUCCESS;
    }
}
